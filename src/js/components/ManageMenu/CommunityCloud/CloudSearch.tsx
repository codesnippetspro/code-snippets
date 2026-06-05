import { __ } from '@wordpress/i18n'
import React from 'react'
import { Spinner } from '@wordpress/components'
import { TablePagination } from '../../common/ListTable/TablePagination'
import { SearchResult } from './SearchResult'
import { useCloudSearch } from './WithCloudSearchContext'
import { SearchFilters } from './SearchFilters'
import type { TablePaginationProps } from '../../common/ListTable/TablePagination'
import type { FormEventHandler } from 'react'

const SearchBox = () => {
	const { searchParams, updateSearchParams, isSearching, doSearch } = useCloudSearch()

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
				value={searchParams.method}
				onChange={event =>
					updateSearchParams({ method: 'codevault' === event.target.value ? 'codevault' : 'term' })}
			>
				<option value="term">{__('Search by keyword', 'code-snippets')}</option>
				<option value="codevault">{__('Name of codevault', 'code-snippets')}</option>
			</select>

			<div className="cloud-search-query">
				<input
					id="cloud-search-query"
					type="search"
					value={searchParams.query}
					aria-label={__('Search query', 'code-snippets')}
					onChange={event => updateSearchParams({ query: event.target.value })}
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
	const { searchResults, isSearching, doSearch } = useCloudSearch()

	if (!searchResults) {
		return null
	}

	const { totalItems, totalPages, page } = searchResults

	const paginationProps: Omit<TablePaginationProps, 'which'> = {
		totalItems,
		totalPages,
		disabled: isSearching,
		currentPage: page,
		setCurrentPage: newPage => doSearch({ page: newPage })
	}

	return (
		<>
			<div className="tablenav top">
				<SearchFilters />

				<TablePagination which="top" {...paginationProps} />
			</div>

			<ul className="cloud-search-results">
				{searchResults.snippets.map(result =>
					<SearchResult key={result.id} snippet={result} />)}
			</ul>

			<div className="tablenav bottom">
				<TablePagination which="bottom" {...paginationProps} />
			</div>
		</>
	)
}

const SearchResults = () => {
	const { searchResults, searchParams, isErrored } = useCloudSearch()

	if (isErrored) {
		return (
			<div className="banner banner-error">
				<p>{__('An error occurred while fetching search results. Please try again.', 'code-snippets')}</p>
			</div>
		)
	}

	if (!searchResults) {
		return null
	}

	if (0 < searchResults.page && 0 === searchResults.snippets.length) {
		return (
			<div className="banner banner-neutral no-results">
				<p>{'codevault' === searchParams.method
					? __('Could not find a codevault with that name. Please try again.', 'code-snippets')
					: __('No snippets could be found with that search term. Please try again.', 'code-snippets')
				}</p>
			</div>
		)
	}

	return searchResults.snippets.length
		? <>
			{searchResults.isFeatured
				? <h3 className="cloud-featured-heading">{__('Featured Snippets', 'code-snippets')}</h3>
				: <h3 className="cloud-snippets-heading">{__('Search Results', 'code-snippets')}</h3>}
			<SearchResultsTable />
		</>
		: null
}

export const CloudSearch = () =>
	<div className="cloud-search">
		<SearchBox />
		<SearchResults />
	</div>
