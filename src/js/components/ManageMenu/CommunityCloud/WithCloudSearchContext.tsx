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

const SEARCH_URLS = {
	SEARCH_QUERY: REST_BASES.cloud.snippets,
	FEATURED: `${REST_BASES.cloud.snippets}/featured`
} as const

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

const updateSearchQueryParams = (params: CloudSearchParams) => {
	updateQueryParam(SEARCH_PARAM_VARS.page, params.page)
	updateQueryParam(SEARCH_PARAM_VARS.type, params.type)
	updateQueryParam(SEARCH_PARAM_VARS.query, params.query)
	updateQueryParam(SEARCH_PARAM_VARS.status, params.status.toString())
	updateQueryParam(SEARCH_PARAM_VARS.method, params.method)
	updateQueryParam(SEARCH_PARAM_VARS.status, params.status || undefined)
	updateQueryParam(SEARCH_PARAM_VARS.category, params.category)
}

const buildSearchUrl = (
	baseUrl: string,
	{ query, type, method, status, category, page }: CloudSearchParams
) =>
	buildUrl(baseUrl, {
		query,
		searchByCodevault: 'codevault' === method ? true : undefined,
		per_page: SNIPPETS_PER_PAGE,
		type: type || undefined,
		category: category || undefined,
		status: status || undefined,
		page
	})

const unpackSearchResponse = ({ data }: AxiosResponse<CloudSnippetsSchema>, baseUrl: string) => ({
	page: data.page,
	isFeatured: baseUrl === SEARCH_URLS.FEATURED,
	snippets: data.snippets,
	totalItems: data.total_snippets,
	totalPages: data.total_pages
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

	const makeSearchRequest = useCallback((request: CloudSearchParams) => {
		const requestId = nextRequestId()
		setIsSearching(true)
		const baseUrl = '' === request.query.trim() ? SEARCH_URLS.FEATURED : SEARCH_URLS.SEARCH_QUERY

		api.getResponse<CloudSnippetsSchema>(buildSearchUrl(baseUrl, request))
			.then(response => {
				if (isCurrentRequest(requestId)) {
					setSearchResults(unpackSearchResponse(response, baseUrl))
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
	const [searchParams, setSearchParams] = useState<CloudSearchParams>(fetchSearchQueryParams)
	const [madeInitialRequest, setMadeInitialRequest] = useState(false)

	const updateSearchParams = useCallback(
		(delta: Partial<CloudSearchParams>) => setSearchParams(previous => ({ ...previous, ...delta })),
		[])

	const doSearch = useCallback((paramsDelta?: Partial<CloudSearchParams>) => {
		if (searchParams.query) {
			const request: CloudSearchParams = { ...searchParams, ...paramsDelta }
			updateSearchQueryParams(request)
			setSearchParams(request)
			makeSearchRequest(request)
		}
	}, [makeSearchRequest, searchParams])

	useEffect(() => {
		if (!madeInitialRequest) {
			makeSearchRequest(searchParams)
			setMadeInitialRequest(true)
		}
	}, [makeSearchRequest, searchParams, madeInitialRequest])

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
