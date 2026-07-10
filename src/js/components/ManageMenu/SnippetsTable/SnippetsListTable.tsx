import { __, _n, _x, sprintf } from '@wordpress/i18n'
import React, { Fragment, useEffect, useMemo, useState } from 'react'
import classnames from 'classnames'
import { createInterpolateElement } from '@wordpress/element'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { REST_BASES } from '../../../utils/restAPI'
import { getSnippetType, isSnippetActive } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ListTable } from '../../common/ListTable'
import { SubmitButton } from '../../common/SubmitButton'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { SnippetsCardGrid } from './SnippetsCardGrid'
import { SearchArea, SearchResultsIndicator } from './SnippetsTableSearch'
import { BULK_ACTIONS, TRASHED_BULK_ACTIONS, useApplyBulkAction } from './useApplyBulkAction'
import { getTableColumns } from './TableColumns'
import type { ListTableAction } from '../../common/ListTable'
import type { SnippetStatus } from './WithSnippetsTableFilters'
import type { SnippetsTableAction } from './useApplyBulkAction'
import type { Snippet } from '../../../types/Snippet'
import type { SnippetView } from '../../../types/SnippetView'
import type { ReactNode } from 'react'

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
							aria-current={currentStatus === status ? 'page' : undefined}
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
						{index < visibleStatuses.length - 1 && ' | '}
					</li>
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

interface ManageTableSettings {
	hiddenColumns: Set<string>
	truncateRowValues: boolean
}

const useManageTableSettings = (): ManageTableSettings => {
	const [hiddenColumns, setHiddenColumns] = useState(() => new Set(window.CODE_SNIPPETS_MANAGE?.hiddenColumns ?? []))
	const [truncateRowValues, setTruncateRowValues] = useState(
		() => 0 !== Number(window.CODE_SNIPPETS_MANAGE?.truncateRowValues ?? 1)
	)

	useEffect(() => {
		const screenOptions = document.getElementById('adv-settings')

		if (!screenOptions) {
			return
		}

		const updateHiddenColumns = () => {
			setHiddenColumns(
				new Set(Array.from(screenOptions.querySelectorAll<HTMLInputElement>('.hide-column-tog:not(:checked)'))
					.map(toggle => toggle.value))
			)

			setTruncateRowValues(
				screenOptions.querySelector<HTMLInputElement>('#snippets-table-truncate-row-values')?.checked ?? true
			)
		}

		updateHiddenColumns()
		screenOptions.addEventListener('change', updateHiddenColumns)

		return () => {
			screenOptions.removeEventListener('change', updateHiddenColumns)
		}
	}, [])

	return { hiddenColumns, truncateRowValues }
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
			<label htmlFor="snippets-tag-filter" className="screen-reader-text">
				{__('Filter snippets by tag', 'code-snippets')}
			</label>
			<select
				id="snippets-tag-filter"
				name="tag"
				value={currentTag}
				aria-label={__('Filter snippets by tag', 'code-snippets')}
				onChange={event => setCurrentTag(event.target.value)}
			>
				<option value="">{__('Show all tags', 'code-snippets')}</option>
				{[...tagsList].map(tag =>
					<option key={tag} value={tag}>{tag}</option>)}
			</select>
		</div>
		: null
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
			{
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				a: <a href={buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type: currentType })} />
			}
		)}
		</>
}

const getRowClassName = (snippet: Snippet, activeByCondition: Map<Snippet['id'], Snippet[]>): string =>
	classnames(
		'snippet',
		`snippet ${isSnippetActive(snippet, activeByCondition) ? 'active' : 'inactive'}-snippet`,
		`${getSnippetType(snippet)}-snippet`,
		`${snippet.scope}-snippet`,
		{
			'trashed-snippet': snippet.trashed
		}
	)

interface SnippetsViewProps {
	snippetView: SnippetView
	snippets: Snippet[]
	actions: ListTableAction<SnippetsTableAction>[]
	doAction: (action: SnippetsTableAction, selected: Set<Snippet['id']>) => Promise<void>
	extraTableNav: (which: 'top' | 'bottom') => ReactNode
	hiddenColumns: Set<string>
	truncateRowValues: boolean
}

const SnippetsView: React.FC<SnippetsViewProps> = ({
	snippetView,
	snippets,
	actions,
	doAction,
	extraTableNav,
	hiddenColumns,
	truncateRowValues
}) => {
	const { activeByCondition } = useFilteredSnippets()
	const columns = useMemo(() => getTableColumns(hiddenColumns), [hiddenColumns])
	const itemsPerPage = window.CODE_SNIPPETS_MANAGE?.snippetsPerPage
	const pageCount = itemsPerPage && Math.ceil(snippets.length / itemsPerPage)

	return 'card' === snippetView
		? <SnippetsCardGrid
			snippets={snippets}
			actions={actions}
			doAction={doAction}
			itemsPerPage={itemsPerPage}
			extraTableNav={extraTableNav}
			noItems={<NoItemsMessage />}
			beforeGrid={<SearchResultsIndicator />}
		/>
		: <ListTable
			items={snippets}
			getKey={snippet => snippet.id}
			className={classnames({ 'truncate-row-values': truncateRowValues })}
			columns={columns}
			actions={actions}
			doAction={doAction}
			totalPages={pageCount}
			extraTableNav={extraTableNav}
			rowClassName={snippet => getRowClassName(snippet, activeByCondition)}
			noItems={<NoItemsMessage />}
			beforeTable={<SearchResultsIndicator />}
		/>
}

export interface SnippetsListTableProps {
	snippetView: SnippetView
}

export const SnippetsListTable: React.FC<SnippetsListTableProps> = ({ snippetView }) => {
	const { snippetsByStatus } = useFilteredSnippets()
	const { currentStatus, setCurrentStatus } = useSnippetsFilters()
	const { hiddenColumns, truncateRowValues } = useManageTableSettings()

	const currentSnippets = useMemo(
		() => snippetsByStatus.get(currentStatus) ?? [],
		[snippetsByStatus, currentStatus]
	)
	const applyBulkAction = useApplyBulkAction(currentSnippets)

	useEffect(() => {
		if (INDEX_STATUS !== currentStatus && !snippetsByStatus.has(currentStatus)) {
			setCurrentStatus(INDEX_STATUS)
		}
	}, [currentStatus, setCurrentStatus, snippetsByStatus])

	const extraTableNav = (which: 'top' | 'bottom') =>
		<>
			{'top' === which && <FilterByTagControl visibleSnippets={snippetsByStatus.get('all') ?? []} />}
			<ClearRecentlyActiveButton />

			<span className="displaying-num" role="status" aria-live="polite">
				{sprintf(
				// translators: %d: total number of snippets across all pages.
					_n('%d item', '%d items', currentSnippets.length),
					currentSnippets.length
				)}
			</span>
		</>

	return (
		<>
			<div className="snippets-table-toolbar">
				<SnippetStatusCounts />
				<SearchArea />
			</div>

			<div className="snippets-list-view">
				<SnippetsView
					snippetView={snippetView}
					snippets={currentSnippets}
					actions={'trashed' === currentStatus ? TRASHED_BULK_ACTIONS : BULK_ACTIONS}
					doAction={applyBulkAction}
					extraTableNav={extraTableNav}
					hiddenColumns={hiddenColumns}
					truncateRowValues={truncateRowValues}
				/>
			</div>
		</>
	)
}
