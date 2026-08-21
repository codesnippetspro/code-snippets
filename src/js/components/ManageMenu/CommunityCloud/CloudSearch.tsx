import { __ } from '@wordpress/i18n'
import React, { useId, useState } from 'react'
import classnames from 'classnames'
import { Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { isCloudSnippetDownloadable } from '../../../utils/snippets/snippets'
import { TableNav } from '../../common/ListTable/TableNavigation'
import { LoadingStatusNotices } from '../../common/LoadingStatusNotices'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
import { CloudSnippetsTable } from './CloudSnippetsTable'
import { CloudSnippetAuthor, SearchResult } from './SearchResult'
import { useCloudSearch } from './WithCloudSearchContext'
import { SearchFilters } from './SearchFilters'
import type { TableNavProps } from '../../common/ListTable/TableNavigation'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { SnippetView } from '../../../types/SnippetView'
import type { ListTableAction } from '../../common/ListTable'
import type { Dispatch, FormEventHandler, SetStateAction } from 'react'

const SearchBox = () => {
	const { searchParams, updateSearchParams, isSearching, doSearch } = useCloudSearch()
	const searchMethodId = useId()
	const [query, setQuery] = useState(searchParams.query)

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
		void doSearch({ query })
	}

	return (
		<form className="cloud-search-form" onSubmit={handleSubmit}>
			<label htmlFor={searchMethodId} className="screen-reader-text">
				{__('Search method', 'code-snippets')}
			</label>
			<select
				id={searchMethodId}
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
					value={query}
					aria-label={__('Search query', 'code-snippets')}
					onChange={event => setQuery(event.target.value)}
					placeholder={__('e.g. Remove unused JavaScript…', 'code-snippets')}
				/>
				{isSearching && (
					<span role="status" aria-live="polite">
						<span className="screen-reader-text">{__('Searching…', 'code-snippets')}</span>
					</span>)}
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
				author={<CloudSnippetAuthor codevaultSlug={result.codevault} />}
				isSelected={selected.has(result.id)}
				onSelectedChange={isCloudSnippetDownloadable(result)
					? isSelected => {
						setSelected(previous => {
							const updated = new Set(previous)

							if (isSelected) {
								updated.add(result.id)
							} else {
								updated.delete(result.id)
							}

							return updated
						})
					}
					: undefined}
			/>)}
	</ul>

interface SearchResultsViewProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

type CloudSearchAction = 'download'

const CLOUD_BULK_ACTIONS: ListTableAction<CloudSearchAction>[] = [
	{ key: 'download', label: __('Download', 'code-snippets') }
]

const useSearchResultsSelection = () => {
	const { api } = useRestAPI()
	const { searchResults, isSearching, doSearch } = useCloudSearch()
	const [selected, setSelected] = useState<Set<CloudSnippetSchema['id']>>(new Set())

	const doAction = async (
		action: CloudSearchAction | undefined,
		selectedIds: Set<CloudSnippetSchema['id']>
	): Promise<void> => {
		if ('download' === action) {
			await Promise.all((searchResults?.snippets ?? [])
				.filter(snippet => selectedIds.has(snippet.id) && isCloudSnippetDownloadable(snippet))
				.map(({ id }) => api.post(`${REST_BASES.cloud.snippets}/${id}/download`)))

			await doSearch()
			setSelected(new Set())
		}
	}

	return { doAction, doSearch, isSearching, searchResults, selected, setSelected }
}

interface SearchResultsTableNavProps extends Partial<TableNavProps<number, CloudSearchAction>> {
	which: 'top' | 'bottom'
}

const SearchResultsTableNav: React.FC<SearchResultsTableNavProps> = ({ which, ...props }) => {
	const { doSearch, isSearching, searchResults, selected, setSelected } = useSearchResultsSelection()

	if (!searchResults) {
		return null
	}

	return (
		<TableNav
			which={which}
			disabled={isSearching}
			selected={selected}
			setSelected={setSelected}
			totalItems={searchResults.totalItems}
			totalPages={searchResults.totalPages}
			currentPage={searchResults.page}
			setCurrentPage={page => void doSearch({ page })}
			{...props}
		/>
	)
}

const SearchResultsTable: React.FC<SearchResultsViewProps> = ({ snippetView, setSnippetView }) => {
	const { doAction, searchResults, selected, setSelected } = useSearchResultsSelection()

	if (!searchResults) {
		return null
	}

	return (
		<div className="snippets-list-view">
			<SearchResultsTableNav
				which="top"
				actions={CLOUD_BULK_ACTIONS}
				doAction={doAction}
				selectAllKeys={'card' === snippetView
					? searchResults.snippets
						.filter(snippet => isCloudSnippetDownloadable(snippet))
						.map(snippet => snippet.id)
					: undefined}
				extraTableNav={() => <SearchFilters />}
				endTableNav={which =>
					'top' === which
						? <SnippetViewToggle snippetView={snippetView} setSnippetView={setSnippetView} />
						: null}
			/>

			{'card' === snippetView
				? <SearchResultsGrid
					snippets={searchResults.snippets}
					selected={selected}
					setSelected={setSelected}
				/>
				: <CloudSnippetsTable
					snippets={searchResults.snippets}
					selected={selected}
					setSelected={setSelected}
				/>}

			<SearchResultsTableNav which="bottom" />
		</div>
	)
}

const SearchResults: React.FC<SearchResultsViewProps> = ({ snippetView, setSnippetView }) => {
	const { searchResults, searchParams } = useCloudSearch()

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
				? <h2 className="cloud-featured-heading">{__('Featured Snippets', 'code-snippets')}</h2>
				: <h2 className="cloud-snippets-heading">{__('Search Results', 'code-snippets')}</h2>}
			<SearchResultsTable snippetView={snippetView} setSnippetView={setSnippetView} />
		</>
		: null
}

const SearchStatus = () => {
	const { isErrored, isLoading } = useCloudSearch()

	return isErrored || isLoading
		? <LoadingStatusNotices
			isLoading={isLoading}
			errorMessage={isErrored
				? __('An error occurred while fetching search results. Please try again.', 'code-snippets')
				: undefined}
			loadingNotice={__('Loading snippets from cloud…', 'code-snippets')}
			noticeLabel={__('Community snippets status', 'code-snippets')}
		/>
		: null
}

export interface CloudSearchProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

export const CloudSearch: React.FC<CloudSearchProps> = ({ snippetView, setSnippetView }) =>
	<div className="cloud-search">
		<SearchBox />
		<SearchStatus />
		<SearchResults snippetView={snippetView} setSnippetView={setSnippetView} />
	</div>
