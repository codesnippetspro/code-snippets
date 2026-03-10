import { __, _x, sprintf } from '@wordpress/i18n'
import React, { Fragment, useEffect, useMemo } from 'react'
import { createInterpolateElement } from '@wordpress/element'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadBulkSnippetExportFile } from '../../../utils/files'
import { REST_BASES } from '../../../utils/restAPI'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ListTable } from '../../common/ListTable'
import { SubmitButton } from '../../common/SubmitButton'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { TableColumns } from './TableColumns'
import type { SnippetStatus} from './WithSnippetsTableFilters'
import type { ListTableBulkAction } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'

const STATUS_LABELS: [SnippetStatus, string][] = [
	['all', __('All', 'code-snippets')],
	['active', __('Active', 'code-snippets')],
	['inactive', __('Inactive', 'code-snippets')],
	['recently_active', __('Recently Active', 'code-snippets')],
	['locked', __('Locked', 'code-snippets')],
	['unlocked', __('Unlocked', 'code-snippets')],
	['trashed', __('Trashed', 'code-snippets')]
]

const SnippetStatusCounts = () => {
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { snippetsByStatus } = useFilteredSnippets()

	const visibleStatuses = STATUS_LABELS.filter(([status]) =>
		snippetsByStatus.has(status) && ('unlocked' !== status || snippetsByStatus.has('locked')))

	return (
		<ul className="subsubsub">
			{visibleStatuses.map(([status, label], index) =>
				<Fragment key={status}>
					<li className={status}>
						<a
							href={buildUrl(window.location.href, { status: INDEX_STATUS === status ? undefined : status })}
							className={currentStatus === status ? 'current' : undefined}
							onClick={event => {
								event.preventDefault()
								setCurrentStatus(status)
							}}
						>
							{`${label} `}
							<span className="count">{
								// translators: %d: number of snippets in the current view.
								sprintf(_x('(%d)', 'table view count', 'code-snippets'), snippetsByStatus.get(status)?.length ?? 0)
							}</span>
						</a>
					</li>
					{index < visibleStatuses.length - 1 && ' | '}
				</Fragment>)}
		</ul>
	)
}

const ClearRecentlyActiveButton: React.FC = () => {
	const { api } = useRestAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const { currentStatus } = useSnippetsFilters()

	return 'recently_active' === currentStatus
		? <div className="alignleft actions">
			<SubmitButton
				secondary
				name="clear-recent-list"
				text={__('Clear List', 'code-snippets')}
				onClick={event => {
					event.preventDefault()
					api.del(REST_BASES.recentlyActive)
						.then(refreshSnippetsList)
						.catch(handleUnknownError)
				}}
			/>
		</div>
		: null
}

interface ExtraTableNavProps {
	visibleSnippets: Snippet[]
}

const FilterByTagControl: React.FC<ExtraTableNavProps> = ({ visibleSnippets }) => {
	const { currentTag, setCurrentTag } = useSnippetsFilters()

	const tagsList: Set<string> = useMemo(
		() => visibleSnippets.reduce((tags, snippet) => {
			snippet.tags.forEach(tag => tags.add(tag))
			return tags
		}, new Set<string>()),
		[visibleSnippets])

	return 0 < tagsList.size
		? <div className="alignleft actions">
			<select
				name="tag"
				value={currentTag}
				onChange={event => setCurrentTag(event.target.value)}
			>
				<option value="">{__('Show all tags', 'code-snippets')}</option>
				{[...tagsList].map(tag =>
					<option key={tag} value={tag}>{tag}</option>)}
			</select>
		</div>
		: null
}

const SearchBox = () => {
	const { searchQuery, setSearchQuery } = useSnippetsFilters()

	return (
		<p className="search-box">
			<label className="screen-reader-text" htmlFor="snippets_search">{__('Search Snippets:', 'code-snippets')}</label>
			<input
				type="search"
				id="snippets_search"
				name="s"
				value={searchQuery ?? ''}
				onChange={event => setSearchQuery(event.target.value)}
				placeholder={__('Search snippets', 'code-snippets')}
			/>
		</p>
	)
}

const NoItemsMessage = () => {
	const { currentType, currentTag, searchQuery } = useSnippetsFilters()

	return searchQuery || currentTag
		? <>
			{__('No snippets were found matching the current search query.', 'code-snippets')}
			{__(' Please enter a new query or use the "Clear Filters" button above.', 'code-snippets')}
		</>
		: <>{createInterpolateElement(
			currentType
				? __("It looks like you don't have any snippets of this type. <a>Perhaps you would like to add a new one?</a>", 'code-snippets')
				: __("It looks like you don't have any snippets. <a>Perhaps you would like to add a new one?</a>", 'code-snippets'),
			{ a: <a href={buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type: currentType })} /> }
		)}
		</>
}

export const SnippetsListTable: React.FC = () => {
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { snippetsByStatus } = useFilteredSnippets()

	const allSnippets = useMemo(() => snippetsByStatus.get('all') ?? [], [snippetsByStatus])
	const totalItems = snippetsByStatus.get(currentStatus)?.length ?? 0
	const itemsPerPage = window.CODE_SNIPPETS_MANAGE?.snippetsPerPage
	const actions: ListTableBulkAction<Snippet['id']>[] = useMemo(
		() => [
			{
				name: __('Activate', 'code-snippets'),
				apply: () => Promise.resolve()
			},
			{
				name: __('Deactivate', 'code-snippets'),
				apply: () => Promise.resolve()
			},
			{
				name: __('Clone', 'code-snippets'),
				apply: () => Promise.resolve()
			},
			{
				name: __('Export', 'code-snippets'),
				apply: (selected: Set<Snippet['id']>) => {
					downloadBulkSnippetExportFile(
						allSnippets.filter(snippet => selected.has(snippet.id))
					)
					return Promise.resolve()
				}
			},
			{
				name: __('Export code', 'code-snippets'),
				apply: () => Promise.resolve()
			},
			{
				name: __('Trash', 'code-snippets'),
				apply: () => Promise.resolve()
			}
		],
		[allSnippets]
	)

	useEffect(() => {
		if (INDEX_STATUS !== currentStatus && !snippetsByStatus.has(currentStatus)) {
			setCurrentStatus(INDEX_STATUS)
		}
	}, [currentStatus, setCurrentStatus, snippetsByStatus])

	return (
		<>
			<SnippetStatusCounts />
			<SearchBox />

			<ListTable
				items={snippetsByStatus.get(currentStatus) ?? []}
				getKey={snippet => snippet.id}
				columns={TableColumns}
				actions={actions}
				totalPages={itemsPerPage && Math.ceil(totalItems / itemsPerPage)}
				extraTableNav={which =>
					<>
						{'top' === which && <FilterByTagControl visibleSnippets={snippetsByStatus.get('all') ?? []} />}
						<ClearRecentlyActiveButton />
					</>}
				rowClassName={snippet =>
					`snippet ${snippet.active ? 'active' : 'inactive'}-snippet ${getSnippetType(snippet)}-snippet ${snippet.scope}-snippet`}
				noItems={<NoItemsMessage />}
			/>
		</>
	)
}
