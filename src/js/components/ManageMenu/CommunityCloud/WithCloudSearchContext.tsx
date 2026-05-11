import React, { useCallback, useEffect, useRef, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { buildUrl, fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { CloudSearchFilters } from './SearchFilters'

const SEARCH_PARAM = 's'
const SEARCH_METHOD_PARAM = 'by'
const DEFAULT_SNIPPETS_PER_PAGE = 10
const MAX_CLOUD_RESULTS_PER_PAGE = 100
const SEARCH_DEBOUNCE_MS = 500

export interface AvailableFilters {
	categories?: string[]
	types?: string[]
	statuses?: { id: number; name: string }[]
}

export interface CloudSearchContext {
	page: number
	error: boolean
	query: string
	doSearch: VoidFunction
	totalItems: number
	totalPages: number
	isSearching: boolean
	isFeatured: boolean
	searchResults: CloudSnippetSchema[] | undefined
	availableFilters: AvailableFilters
	setPage: Dispatch<SetStateAction<number>>
	setQuery: Dispatch<SetStateAction<string>>
	searchByCodevault: boolean
	setSearchByCodevault: Dispatch<SetStateAction<boolean>>
	filters: CloudSearchFilters
	setFilters: Dispatch<SetStateAction<CloudSearchFilters>>
}

const [Context, useCloudSearch] = createContextHook<CloudSearchContext>('useCloudSearch')

export const WithCloudSearchContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { api } = useRestAPI()
	const [page, setPage] = useState(1)
	const [query, setQuery] = useState(() => fetchQueryParam(SEARCH_PARAM) ?? '')
	const [searchByCodevault, setSearchByCodevault] = useState(() => 'codevault' === fetchQueryParam(SEARCH_METHOD_PARAM))
	const snippetsPerPage = Math.min(
		window.CODE_SNIPPETS_MANAGE?.cloudSearchPerPage ?? window.CODE_SNIPPETS_MANAGE?.snippetsPerPage ?? DEFAULT_SNIPPETS_PER_PAGE,
		MAX_CLOUD_RESULTS_PER_PAGE
	)

	const [filters, setFilters] = useState<CloudSearchFilters>(() => ({
		category: fetchQueryParam('category') ?? '',
		type: fetchQueryParam('type') ?? '',
		status: Number(fetchQueryParam('status') ?? 0)
	}))

	const [totalItems, setTotalItems] = useState(0)
	const [totalPages, setTotalPages] = useState(0)
	const [availableFilters, setAvailableFilters] = useState<AvailableFilters>({})

	const [searchResults, setSearchResults] = useState<CloudSnippetSchema[] | undefined>()
	const [isSearching, setIsSearching] = useState(false)
	const [error, setError] = useState(false)
	const [isFeatured, setIsFeatured] = useState(false)

	const searchTimerRef = useRef<ReturnType<typeof setTimeout>>()
	const activeRequestRef = useRef(0)

	const nextRequestId = useCallback(() => {
		activeRequestRef.current += 1
		return activeRequestRef.current
	}, [])

	const filterParams = useCallback((): Record<string, string | number> => {
		const params: Record<string, string | number> = {}
		if (filters.category) {params.category = filters.category}
		if (filters.type) {params.type = filters.type}
		if (filters.status) {params.status = filters.status}
		return params
	}, [filters])

	const doSearch = useCallback(() => {
		if (!query) {
			return
		}

		clearTimeout(searchTimerRef.current)

		searchTimerRef.current = setTimeout(() => {
			const requestId = nextRequestId()

			setIsFeatured(false)
			updateQueryParam(SEARCH_PARAM, query)
			updateQueryParam(SEARCH_METHOD_PARAM, searchByCodevault ? 'codevault' : 'term')
			setIsSearching(true)

			api.getResponse<CloudSnippetSchema[]>(
				buildUrl(REST_BASES.cloud, { query, searchByCodevault, page, per_page: snippetsPerPage, ...filterParams() })
			)
				.then(response => {
					if (requestId !== activeRequestRef.current) {
						return
					}
					setTotalItems(Number(response.headers['x-wp-total']))
					setTotalPages(Number(response.headers['x-wp-totalpages']))
					try { setAvailableFilters(JSON.parse(String(response.headers['x-wp-filters'] ?? '{}')) as AvailableFilters) } catch { /* */ }
					setSearchResults(response.data)
					setIsSearching(false)
				})
				.catch(() => {
					if (requestId !== activeRequestRef.current) {
						return
					}
					setIsSearching(false)
					setError(true)
				})
		}, SEARCH_DEBOUNCE_MS)
	}, [api, filterParams, nextRequestId, page, query, searchByCodevault, snippetsPerPage])

	useEffect(() => {
		if (query) {
			return
		}

		const requestId = nextRequestId()
		setIsSearching(true)

		api.getResponse<CloudSnippetSchema[]>(
			buildUrl(`${REST_BASES.cloud}/featured`, { page, per_page: snippetsPerPage, ...filterParams() })
		)
			.then(response => {
				if (requestId !== activeRequestRef.current) {
					return
				}
				setTotalItems(Number(response.headers['x-wp-total']))
				setTotalPages(Number(response.headers['x-wp-totalpages']))
				try { setAvailableFilters(JSON.parse(String(response.headers['x-wp-filters'] ?? '{}')) as AvailableFilters) } catch { /* */ }
				setSearchResults(response.data)
				setIsFeatured(true)
				setIsSearching(false)
			})
			.catch(() => {
				if (requestId !== activeRequestRef.current) {
					return
				}
				setIsSearching(false)
			})
	}, [api, filterParams, nextRequestId, page, query, snippetsPerPage])

	// Reset to page 1 when filters change.
	const prevFiltersRef = useRef(filters)
	useEffect(() => {
		if (prevFiltersRef.current !== filters) {
			prevFiltersRef.current = filters
			setPage(1)
		}
	}, [filters])

	useEffect(() => () => clearTimeout(searchTimerRef.current), [])

	const value: CloudSearchContext = {
		page,
		error,
		query,
		setPage,
		setQuery,
		doSearch,
		totalItems,
		totalPages,
		isSearching,
		isFeatured,
		searchResults,
		availableFilters,
		searchByCodevault,
		setSearchByCodevault,
		filters,
		setFilters
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useCloudSearch }
