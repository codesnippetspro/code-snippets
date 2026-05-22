import React, { useCallback, useEffect, useRef, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { buildUrl, fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import type { CloudSnippetsSchema } from '../../../types/schema/CloudSnippetsSchema'
import type { PropsWithChildren } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { AxiosResponse } from 'axios'

const DEFAULT_SNIPPETS_PER_PAGE = 10
const MAX_CLOUD_RESULTS_PER_PAGE = 100
const SNIPPETS_PER_PAGE = Math.min(
	window.CODE_SNIPPETS_MANAGE?.cloudSearchPerPage ?? window.CODE_SNIPPETS_MANAGE?.snippetsPerPage ?? DEFAULT_SNIPPETS_PER_PAGE,
	MAX_CLOUD_RESULTS_PER_PAGE
)

const SEARCH_DEBOUNCE_MS = 500

export interface AvailableCloudFilters {
	types?: CloudFilterOption[]
	statuses?: CloudFilterOption[]
	categories?: CloudFilterOption[]
}

const SEARCH_PARAM_VARS: Record<keyof CloudSearchParams, string> = {
	page: 'paged',
	query: 's',
	method: 'by',
	type: 'type',
	status: 'status',
	category: 'category'
}

export interface CloudSearchParams {
	page: number
	query: string
	method: 'term' | 'codevault'
	type: string
	status: number
	category: string
}

export interface CloudSearchResults {
	page: number
	snippets: CloudSnippetSchema[]
	totalItems: number
	totalPages: number
	isFeatured: boolean
}

export interface CloudFilterOption {
	id: number
	name: string
}

const fetchSearchQueryParams = (): CloudSearchParams => {
	const page = Number(fetchQueryParam(SEARCH_PARAM_VARS.page) ?? 1)
	const query = fetchQueryParam(SEARCH_PARAM_VARS.query) ?? ''
	const type = fetchQueryParam(SEARCH_PARAM_VARS.type) ?? ''
	const status = fetchQueryParam(SEARCH_PARAM_VARS.status) ?? 0
	const method = fetchQueryParam(SEARCH_PARAM_VARS.method)
	const category = fetchQueryParam(SEARCH_PARAM_VARS.category) ?? ''

	return {
		page: isNaN(page) ? 1 : page,
		type,
		query,
		status: Number(status),
		method: 'codevault' === method ? 'codevault' : 'term',
		category
	}
}

const updateSearchQueryParams = (request: CloudSearchRequest) => {
	updateQueryParam(SEARCH_PARAM_VARS.page, request.page)
	updateQueryParam(SEARCH_PARAM_VARS.type, request.type)
	updateQueryParam(SEARCH_PARAM_VARS.query, request.query)
	updateQueryParam(SEARCH_PARAM_VARS.status, request.status.toString())
	updateQueryParam(SEARCH_PARAM_VARS.method, request.method)
	updateQueryParam(SEARCH_PARAM_VARS.status, request.status || undefined)
	updateQueryParam(SEARCH_PARAM_VARS.category, request.category)
}

interface CloudSearchRequest extends CloudSearchParams {
	isFeatured: boolean
}

const buildSearchUrl = (
	{ query, type, method, status, category, page, isFeatured }: CloudSearchRequest
) =>
	buildUrl(
		`${REST_BASES.cloud.snippets}${isFeatured ? '/featured' : ''}`,
		{
			query,
			searchByCodevault: 'codevault' === method ? true : undefined,
			per_page: SNIPPETS_PER_PAGE,
			type: type || undefined,
			category: category || undefined,
			status: status || undefined,
			page
		})

const unpackSearchResponse = (
	response: AxiosResponse<CloudSnippetsSchema>,
	{ isFeatured }: CloudSearchRequest
) => ({
	page: response.data.page,
	isFeatured,
	snippets: response.data.snippets,
	totalItems: response.data.total_snippets,
	totalPages: response.data.total_pages
})

const isFilterOption = (value: unknown): value is CloudFilterOption =>
	'object' === typeof value && null !== value &&
	'id' in value && 'number' === typeof value.id &&
	'name' in value && 'string' === typeof value.name

const unpackFilterOptions = (data: unknown): CloudFilterOption[] | undefined =>
	Array.isArray(data)
		? data.filter(isFilterOption)
		: undefined

const unpackFiltersFromResponse = (
	{ data: { available_filters } }: AxiosResponse<CloudSnippetsSchema>
): AvailableCloudFilters | undefined =>
	'object' === typeof available_filters && available_filters
		? {
			...'types' in available_filters && { types: unpackFilterOptions(available_filters.types) },
			...'statuses' in available_filters && { statuses: unpackFilterOptions(available_filters.statuses) },
			...'categories' in available_filters && { categories: unpackFilterOptions(available_filters.categories) }
		}
		: undefined

const useRequestIds = () => {
	const activeRequestRef = useRef(0)

	const nextRequestId = useCallback(() => {
		activeRequestRef.current += 1
		return activeRequestRef.current
	}, [])

	const isCurrentRequest = useCallback((requestId: number) => requestId === activeRequestRef.current, [])

	return { isCurrentRequest, nextRequestId }
}

export interface CloudSearchContext {
	isErrored: boolean
	doSearch: (paramsDelta?: Partial<CloudSearchParams>) => void
	isSearching: boolean
	searchParams: CloudSearchParams
	searchResults: CloudSearchResults | undefined
	availableFilters: AvailableCloudFilters
	updateSearchParams: (params: Partial<CloudSearchParams>) => void
}

const useSearchApi = () => {
	const { api } = useRestAPI()
	const { isCurrentRequest, nextRequestId } = useRequestIds()
	const [isSearching, setIsSearching] = useState(false)
	const [searchResults, setSearchResults] = useState<CloudSearchResults | false | undefined>()
	const [availableFilters, setAvailableFilters] = useState<AvailableCloudFilters>({})

	const makeSearchRequest = useCallback((request: CloudSearchRequest) => {
		const requestId = nextRequestId()
		setIsSearching(true)

		api.getResponse<CloudSnippetsSchema>(buildSearchUrl(request))
			.then(response => {
				if (isCurrentRequest(requestId)) {
					setSearchResults(unpackSearchResponse(response, request))
					setAvailableFilters(previous => unpackFiltersFromResponse(response) ?? previous)
				}
			})
			.catch(() => {
				if (isCurrentRequest(requestId)) {
					setSearchResults(false)
				}
			})
			.finally(() => setIsSearching(false))
	}, [api, nextRequestId, isCurrentRequest])

	return {
		isSearching,
		searchResults,
		availableFilters,
		makeSearchRequest
	}
}

const [Context, useCloudSearch] = createContextHook<CloudSearchContext>('useCloudSearch')

export const WithCloudSearchContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { isSearching, makeSearchRequest, availableFilters, searchResults } = useSearchApi()
	const searchTimerRef = useRef<ReturnType<typeof setTimeout>>()
	const [searchParams, setSearchParams] = useState<CloudSearchParams>(fetchSearchQueryParams)
	const [madeInitialRequest, setMadeInitialRequest] = useState(false)

	const updateSearchParams = useCallback(
		(delta: Partial<CloudSearchParams>) => setSearchParams(previous => ({ ...previous, ...delta })),
		[])

	const doSearch = useCallback((paramsDelta?: Partial<CloudSearchParams>) => {
		if (searchParams.query) {
			clearTimeout(searchTimerRef.current)

			searchTimerRef.current = setTimeout(() => {
				const request: CloudSearchRequest = { ...searchParams, ...paramsDelta, isFeatured: false }
				updateSearchQueryParams(request)
				setSearchParams(request)
				makeSearchRequest(request)
			}, SEARCH_DEBOUNCE_MS)
		}
	}, [makeSearchRequest, searchParams])

	useEffect(() => {
		if (!madeInitialRequest) {
			makeSearchRequest({ ...searchParams, isFeatured: !searchParams.query })
			setMadeInitialRequest(true)
		}
	}, [makeSearchRequest, searchParams, madeInitialRequest])

	useEffect(() => () => clearTimeout(searchTimerRef.current), [])

	const value: CloudSearchContext = {
		doSearch,
		isSearching,
		searchParams,
		availableFilters,
		updateSearchParams,
		...false === searchResults
			? { isErrored: true, searchResults: undefined }
			: { isErrored: false, searchResults }
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useCloudSearch }
