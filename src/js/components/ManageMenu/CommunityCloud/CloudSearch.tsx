import { __ } from '@wordpress/i18n'
import React, { useMemo, useState } from 'react'
import { Spinner } from '@wordpress/components'
import { useCloudSearch } from '../../../hooks/useCloudSearch'
import { fetchQueryParam } from '../../../utils/urls'
import { TablePagination } from '../../common/ListTable/TablePagination'
import { SubmitButton } from '../../common/SubmitButton'
import { SearchFilters } from './SearchFilters'
import { SearchResults } from './SearchResults'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { CloudSearchFilters } from './SearchFilters'
import type { Dispatch, FormEventHandler, SetStateAction } from 'react'

const SearchBox = () => {
	const { query, searchByCodevault, setPage, setQuery, setSearchByCodevault, isSearching } = useCloudSearch()

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
		setPage(1)
	}

	return (
		<form className="cloud-search-form" onSubmit={handleSubmit}>
			<label className="screen-reader-text" htmlFor="cloud-search-method">
				{__('Search method', 'code-snippets')}
			</label>
			<select
				id="cloud-search-method"
				value={searchByCodevault ? 'codevault' : 'term'}
				onChange={event => setSearchByCodevault('codevault' === event.target.value)}
			>
				<option value="term">{__('Search by keyword', 'code-snippets')}</option>
				<option value="codevault">{__('Name of codevault', 'code-snippets')}</option>
			</select>

			<div className="cloud-search-query">
				<label className="screen-reader-text" htmlFor="cloud-search-query">
					{__('Search query', 'code-snippets')}
				</label>
				<input
					id="cloud-search-query"
					type="search"
					value={query}
					onChange={event => setQuery(event.target.value)}
					placeholder={__('e.g. Remove unused JavaScript…', 'code-snippets')}
				/>
				{isSearching && <Spinner />}
			</div>

			<SubmitButton primary text={__('Search Cloud Library', 'code-snippets')} />
		</form>
	)
}

interface SearchResultsTableProps {
	filters: CloudSearchFilters
	setFilters: Dispatch<SetStateAction<CloudSearchFilters>>
	filteredSearchResults: CloudSnippetSchema[]
}

const SearchResultsTable: React.FC<SearchResultsTableProps> = ({ filters, setFilters, filteredSearchResults }) => {
	const { page, totalItems, totalPages, setPage } = useCloudSearch()

	return (
		<>
			<div className="tablenav top">
				<SearchFilters filters={filters} setFilters={setFilters} />

				<TablePagination
					which="top"
					totalItems={totalItems}
					totalPages={totalPages}
					currentPage={page}
					setCurrentPage={setPage}
				/>
			</div>

			<SearchResults results={filteredSearchResults} />

			<div className="tablenav bottom">
				<TablePagination
					which="bottom"
					totalItems={totalItems}
					totalPages={totalPages}
					currentPage={page}
					setCurrentPage={setPage}
				/>
			</div>
		</>
	)
}

const ErrorBanner = () =>
	<div className="banner banner-error">
		<p>{__('An error occurred while fetching search results. Please try again.')}</p>
	</div>

const NoSearchResultsBanner = () =>
	<div className="banner banner-neutral no-results">
		<p>{__('No snippets or codevault could be found with that search term. Please try again.', 'code-snippets')}</p>
	</div>

export const CloudSearch = () => {
	const { searchResults, error, page } = useCloudSearch()

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

	return (
		<div className="cloud-search">
			<SearchBox />

			{error && <ErrorBanner />}

			{0 < page && searchResults && 0 === searchResults.length
				? <NoSearchResultsBanner />
				: filteredSearchResults && <SearchResultsTable {...{ filters, setFilters, filteredSearchResults }} />}
		</div>
	)
}
