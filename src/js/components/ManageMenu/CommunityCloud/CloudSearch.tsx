import { __ } from '@wordpress/i18n'
import React, { useEffect } from 'react'
import { Spinner } from '@wordpress/components'
import { TablePagination } from '../../common/ListTable/TablePagination'
import { SubmitButton } from '../../common/SubmitButton'
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

			<SubmitButton primary disabled={isSearching} text={__('Search Cloud Library', 'code-snippets')} />
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
		: null
}

const ErrorBanner = () =>
	<div className="banner banner-error">
		<p>{__('An error occurred while fetching search results. Please try again.')}</p>
	</div>

const NoSearchResultsBanner = () =>
	<div className="banner banner-neutral no-results">
		<p>{__('No snippets or codevault could be found with that search term. Please try again.', 'code-snippets')}</p>
	</div>

const FeaturedHeading = () =>
	<h3 className="cloud-featured-heading">
		{__('Featured Snippets', 'code-snippets')}
	</h3>

export const CloudSearch = () => {
	const { searchResults, error, page, isFeatured } = useCloudSearch()

	return (
		<div className="cloud-search">
			<SearchBox />

			{error && <ErrorBanner />}

			{isFeatured && searchResults && 0 < searchResults.length
				? <>
					<FeaturedHeading />
					<WithCloudSearchFiltersContext>
						<SearchResultsTable />
					</WithCloudSearchFiltersContext>
				</>
				: 0 < page && 0 === searchResults?.length
					? <NoSearchResultsBanner />
					: <WithCloudSearchFiltersContext>
						<SearchResultsTable />
					</WithCloudSearchFiltersContext>}
		</div>
	)
}
