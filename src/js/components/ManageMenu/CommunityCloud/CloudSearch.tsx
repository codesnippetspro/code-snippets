import { __ } from '@wordpress/i18n'
import React, { useEffect } from 'react'
import { Spinner } from '@wordpress/components'
import { TablePagination } from '../../common/ListTable/TablePagination'
import { useCloudSearch } from './WithCloudSearchContext'
import { WithCloudSearchFiltersContext, useCloudSearchFilters } from './WithCloudSearchFiltersContext'
import { SearchFilters } from './SearchFilters'
import { SearchResults } from './SearchResults'
import type { FormEventHandler } from 'react'

const SearchBox = () => {
	const { query, searchByCodevault, setQuery, setSearchByCodevault, isSearching, doSearch } = useCloudSearch()

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
		doSearch()
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
				<input
					id="cloud-search-query"
					type="search"
					value={query}
					aria-label={__('Search query', 'code-snippets')}
					onChange={event => setQuery(event.target.value)}
					placeholder={__('e.g. Remove unused JavaScript…', 'code-snippets')}
				/>
				<span role="status" aria-live="polite">
					{isSearching &&
						<span className="screen-reader-text">{__('Searching…', 'code-snippets')}</span>}
				</span>
			</div>

			<button
				type="submit"
				className="button button-primary cloud-search-submit"
				disabled={isSearching}
			>
				{isSearching ? <Spinner /> : __('Search Cloud Library', 'code-snippets')}
			</button>
		</form>
	)
}

const SearchResultsTable = () => {
	const { page, totalItems, totalPages, setPage, doSearch, isSearching } = useCloudSearch()
	const { filteredSearchResults } = useCloudSearchFilters()

	useEffect(() => {
		doSearch()
	}, [doSearch, page])

	return filteredSearchResults
		? <>
			<div className="tablenav top">
				<SearchFilters />

				<TablePagination
					which="top"
					totalItems={totalItems}
					totalPages={totalPages}
					disabled={isSearching}
					currentPage={page}
					setCurrentPage={setPage}
				/>
			</div>

			{0 < filteredSearchResults.length
				? <>
					<SearchResults results={filteredSearchResults} />

					<div className="tablenav bottom">
						<TablePagination
							which="bottom"
							totalItems={totalItems}
							totalPages={totalPages}
							disabled={isSearching}
							currentPage={page}
							setCurrentPage={setPage}
						/>
					</div>
				</>
				: <NoSearchResultsBanner />}
		</>
		: null
}

const ErrorBanner = () =>
	<div className="banner banner-error" role="alert">
		<p>{__('An error occurred while fetching search results. Please try again.')}</p>
	</div>

const NoSearchResultsBanner = () =>
	<div className="banner banner-neutral no-results" role="status" aria-live="polite">
		<p>{__('No snippets or codevault could be found with that search term. Please try again.', 'code-snippets')}</p>
	</div>

const CloudSnippetsHeading: React.FC<{ isFeatured: boolean }> = ({ isFeatured }) =>
	<h3 className="cloud-snippets-heading">
		{isFeatured
			? __('Featured Snippets', 'code-snippets')
			: __('Search Results', 'code-snippets')}
	</h3>

export const CloudSearch = () => {
	const { searchResults, error, isFeatured } = useCloudSearch()

	return (
		<div className="cloud-search">
			<SearchBox />

			{error && <ErrorBanner />}

			{searchResults !== undefined
				? <>
					<CloudSnippetsHeading isFeatured={isFeatured} />
					<WithCloudSearchFiltersContext>
						<SearchResultsTable />
					</WithCloudSearchFiltersContext>
				</>
				: null}
		</div>
	)
}
