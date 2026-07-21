import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import classnames from 'classnames'
import { Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { isLicensed } from '../../../utils/screen'
import { isProSnippet } from '../../../utils/snippets/snippets'
import { TableNav } from '../../common/ListTable/TableNavigation'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
import { CloudSnippetsTable } from './CloudSnippetsTable'
import { CloudSnippetAuthor, SearchResult } from './SearchResult'
import { useCloudSearch } from './WithCloudSearchContext'
import { SearchFilters } from './SearchFilters'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { SnippetView } from '../../../types/SnippetView'
import type { ListTableAction } from '../../common/ListTable/ListTable'
import type { Dispatch, FormEventHandler, SetStateAction } from 'react'

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
				<option value="codevault">{__('Name of library', 'code-snippets')}</option>
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

const isSnippetDownloadable = (snippet: CloudSnippetSchema): boolean =>
	!snippet.local_id && (isLicensed() || !isProSnippet(snippet))

interface SearchResultsGridProps {
	snippets: CloudSnippetSchema[]
	selected: Set<CloudSnippetSchema['id']>
	setSelected: Dispatch<SetStateAction<Set<CloudSnippetSchema['id']>>>
}

const SearchResultsGrid: React.FC<SearchResultsGridProps> = ({ snippets, selected, setSelected }) =>
	<ul
		className={classnames('cloud-search-results', 'code-snippets-cards', {
			'has-selection': snippets.some(snippet => selected.has(snippet.id))
		})}
	>
		{snippets.map(result =>
			<SearchResult
				key={result.id}
				snippet={result}
				author={<CloudSnippetAuthor snippet={result} />}
				isSelected={selected.has(result.id)}
				onSelectedChange={isSelected => {
					setSelected(previous => {
						const updated = new Set(previous)

						if (isSelected) {
							updated.add(result.id)
						} else {
							updated.delete(result.id)
						}

						return updated
					})
				}}
			/>)}
	</ul>

interface SearchResultsViewProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

const CLOUD_BULK_ACTIONS: ListTableAction<'download'>[] = [
	{ key: 'download', label: __('Download', 'code-snippets') }
]

const useSearchResultsSelection = () => {
	const { api } = useRestAPI()
	const { searchResults, isSearching, doSearch } = useCloudSearch()
	const [selected, setSelected] = useState<Set<CloudSnippetSchema['id']>>(new Set())
	const snippets = searchResults?.snippets

	useEffect(() => {
		setSelected(new Set())
	}, [snippets])

	const doAction = (
		_action: 'download',
		selectedIds: Set<CloudSnippetSchema['id']>
	): Promise<void> => {
		const downloadable = (snippets ?? [])
			.filter(snippet => selectedIds.has(snippet.id) && isSnippetDownloadable(snippet))

		return Promise.all(downloadable.map(snippet =>
			api.post(`${REST_BASES.cloud.snippets}/${snippet.id}/download`)))
			.then(() => doSearch())
	}

	return { doAction, doSearch, isSearching, searchResults, selected, setSelected }
}

const SearchResultsTable: React.FC<SearchResultsViewProps> = ({ snippetView, setSnippetView }) => {
	const { doAction, doSearch, isSearching, searchResults, selected, setSelected } =
		useSearchResultsSelection()

	if (!searchResults) {
		return null
	}

	const { totalItems, totalPages, page } = searchResults

	const navProps = {
		totalItems,
		totalPages,
		selected,
		setSelected,
		disabled: isSearching,
		currentPage: page,
		setCurrentPage: (newPage: number) => doSearch({ page: newPage })
	}

	return (
		<div className="snippets-list-view">
			<TableNav
				which="top"
				actions={CLOUD_BULK_ACTIONS}
				doAction={doAction}
				bulkSelectLabel={__('Bulk actions', 'code-snippets')}
				selectAllKeys={searchResults.snippets.map(snippet => snippet.id)}
				extraTableNav={() => <SearchFilters />}
				endTableNav={which =>
					'top' === which
						? <SnippetViewToggle snippetView={snippetView} setSnippetView={setSnippetView} />
						: null}
				{...navProps}
			/>

			{'card' === snippetView
				? <SearchResultsGrid
					snippets={searchResults.snippets}
					selected={selected}
					setSelected={setSelected}
				/>
				: <CloudSnippetsTable snippets={searchResults.snippets} />}

			<TableNav which="bottom" {...navProps} />
		</div>
	)
}

const SearchResults: React.FC<SearchResultsViewProps> = ({ snippetView, setSnippetView }) => {
	const { searchResults, searchParams, isErrored } = useCloudSearch()

	if (isErrored) {
		return (
			<div className="banner banner-error">
				<p>{__(
					'An error occurred while fetching search results. Please try again.',
					'code-snippets'
				)}</p>
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
			<SearchResultsTable snippetView={snippetView} setSnippetView={setSnippetView} />
		</>
		: null
}

export interface CloudSearchProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

export const CloudSearch: React.FC<CloudSearchProps> = ({ snippetView, setSnippetView }) =>
	<div className="cloud-search">
		<SearchBox />
		<SearchResults snippetView={snippetView} setSnippetView={setSnippetView} />
	</div>
