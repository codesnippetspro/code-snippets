import React, { useMemo , useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { fetchQueryParam } from '../../../utils/urls'
import { useCloudSearch } from './WithCloudSearchContext'
import type { CloudSearchFilters } from './SearchFilters'
import type { Dispatch, PropsWithChildren, SetStateAction} from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

interface CloudSearchFiltersContext {
	filters: CloudSearchFilters
	setFilters: Dispatch<SetStateAction<CloudSearchFilters>>
	filteredSearchResults?: CloudSnippetSchema[]
}

export const [CloudSearchFiltersContext, useCloudSearchFilters] = createContextHook<CloudSearchFiltersContext>('useCloudSearchFilters')

export const WithCloudSearchFiltersContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { searchResults } = useCloudSearch()

	const [filters, setFilters] = useState<CloudSearchFilters>(() => {
		const tags = fetchQueryParam('tags') ?? ''
		const status = fetchQueryParam('status') ?? 0
		return { tags, status: Number(status) }
	})

	const filteredSearchResults = useMemo(
		() =>
			searchResults?.filter(snippet =>
				(!filters.tags || snippet.tags.includes(filters.tags)) &&
				(!filters.status || snippet.status.valueOf() === filters.status)),
		[searchResults, filters])

	const value: CloudSearchFiltersContext = {
		filters,
		setFilters,
		filteredSearchResults
	}

	return <CloudSearchFiltersContext.Provider value={value}>{children}</CloudSearchFiltersContext.Provider>
}
