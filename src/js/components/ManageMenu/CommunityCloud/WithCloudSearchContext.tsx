import React, { useCallback, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { REST_CLOUD_SEARCH_BASE } from '../../../utils/restAPI'
import { useRestAPI } from '../../../hooks/useRestAPI'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import { buildUrl } from '../../../utils/urls'

interface CloudSearchContext {
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

export const [CloudSearchContext, useCloudSearch] = createContextHook<CloudSearchContext>('useCloudSearch')

export const WithCloudSearchContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { api } = useRestAPI()
	const [page, setPage] = useState(1)
	const [query, setQuery] = useState('')
	const [searchByCodevault, setSearchByCodevault] = useState(false)

	const [totalItems, setTotalItems] = useState(0)
	const [totalPages, setTotalPages] = useState(0)

	const [searchResults, setSearchResults] = useState<CloudSnippetSchema[] | undefined>(() => {
		const results = window.localStorage.getItem('code-snippets-cloud-search') // TODO remove this.
		return results ? JSON.parse(results) as CloudSnippetSchema[] : undefined
	})
	const [isSearching, setIsSearching] = useState(false)
	const [error, setError] = useState(false)

	const doSearch = useCallback(() => {
		setIsSearching(true)

		api
			.getResponse<CloudSnippetSchema[]>(buildUrl(REST_CLOUD_SEARCH_BASE, { query, searchByCodevault, page }))
			.then(response => {
				setTotalItems(Number(response.headers['x-wp-total']))
				setTotalPages(Number(response.headers['x-wp-totalpages']))
				setSearchResults(response.data)
				setIsSearching(false)

				window.localStorage.setItem('code-snippets-cloud-search', JSON.stringify(response.data))
			})
			.catch((error: unknown) => {
				console.error(error)
				setIsSearching(false)
				setError(true)
			})
	}, [api, page, query, searchByCodevault, setError, setSearchResults, setTotalItems, setTotalPages])

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

	return <CloudSearchContext.Provider value={value}>{children}</CloudSearchContext.Provider>
}
