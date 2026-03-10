import React, { useCallback, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { buildUrl, fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

const SEARCH_PARAM = 's'
const SEARCH_METHOD_PARAM = 'by'
const DEFAULT_SNIPPETS_PER_PAGE = 10
const MAX_CLOUD_RESULTS_PER_PAGE = 100

export interface CloudSearchContext {
	page: number
	error: boolean
	query: string
	doSearch: VoidFunction
	totalItems: number
	totalPages: number
	isSearching: boolean
	searchResults: CloudSnippetSchema[] | undefined
	setPage: Dispatch<SetStateAction<number>>
	setQuery: Dispatch<SetStateAction<string>>
	searchByCodevault: boolean
	setSearchByCodevault: Dispatch<SetStateAction<boolean>>
}

const [Context, useCloudSearch] = createContextHook<CloudSearchContext>('useCloudSearch')

export const WithCloudSearchContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { api } = useRestAPI()
	const [page, setPage] = useState(1)
	const [query, setQuery] = useState(() => fetchQueryParam(SEARCH_PARAM) ?? '')
	const [searchByCodevault, setSearchByCodevault] = useState(() => 'codevault' === fetchQueryParam(SEARCH_METHOD_PARAM))
	const snippetsPerPage = Math.min(
		window.CODE_SNIPPETS_MANAGE?.snippetsPerPage ?? DEFAULT_SNIPPETS_PER_PAGE,
		MAX_CLOUD_RESULTS_PER_PAGE
	)

	const [totalItems, setTotalItems] = useState(0)
	const [totalPages, setTotalPages] = useState(0)

	const [searchResults, setSearchResults] = useState<CloudSnippetSchema[] | undefined>()
	const [isSearching, setIsSearching] = useState(false)
	const [error, setError] = useState(false)

	const doSearch = useCallback(() => {
		if (query) {
			updateQueryParam(SEARCH_PARAM, query)
			updateQueryParam(SEARCH_METHOD_PARAM, searchByCodevault ? 'codevault' : 'term')
			setIsSearching(true)

			api.getResponse<CloudSnippetSchema[]>(
				buildUrl(REST_BASES.cloud, { query, searchByCodevault, page, per_page: snippetsPerPage })
			)
				.then(response => {
					console.log(response.headers)
					setTotalItems(Number(response.headers['x-wp-total']))
					setTotalPages(Number(response.headers['x-wp-totalpages']))
					setSearchResults(response.data)
					setIsSearching(false)
				})
				.catch((error: unknown) => {
					console.error(error)
					setIsSearching(false)
					setError(true)
				})
		}
	}, [api, page, query, searchByCodevault, snippetsPerPage])

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
		searchResults,
		searchByCodevault,
		setSearchByCodevault
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useCloudSearch }
