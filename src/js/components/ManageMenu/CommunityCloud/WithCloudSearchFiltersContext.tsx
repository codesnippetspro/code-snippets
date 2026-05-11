import React from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { useCloudSearch } from './WithCloudSearchContext'
import type { CloudSearchFilters } from './SearchFilters'
import type { Dispatch, PropsWithChildren, SetStateAction } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

interface CloudSearchFiltersContext {
	filters: CloudSearchFilters
	setFilters: Dispatch<SetStateAction<CloudSearchFilters>>
	filteredSearchResults?: CloudSnippetSchema[]
}

const [Context, useCloudSearchFilters] = createContextHook<CloudSearchFiltersContext>('useCloudSearchFilters')

export const WithCloudSearchFiltersContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { searchResults, filters, setFilters } = useCloudSearch()

	const value: CloudSearchFiltersContext = {
		filters,
		setFilters,
		filteredSearchResults: searchResults
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useCloudSearchFilters }
