import { __ } from '@wordpress/i18n'
import React, { useEffect, useMemo, useState } from 'react'
import classnames from 'classnames'
import { getSnippetType, isSnippetActive } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ListTable } from '../../common/ListTable'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
import { SnippetsCardGrid } from './SnippetsCardGrid'
import { SnippetsTableNavigation, SnippetsTableToolbar } from './SnippetsTableControls'
import { SearchResultsIndicator } from './SnippetsTableSearch'
import { getTableColumns } from './TableColumns'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { BULK_ACTIONS, TRASHED_BULK_ACTIONS, useApplyBulkAction } from './useApplyBulkAction'
import type { ListTableAction } from '../../common/ListTable'
import type { SnippetsTableAction } from './useApplyBulkAction'
import type { Snippet } from '../../../types/Snippet'
import type { SnippetView } from '../../../types/SnippetView'
import type { ReactNode } from 'react'

interface ManageTableSettings {
	hiddenColumns: Set<string>
	truncateRowValues: boolean
}

const useManageTableSettings = (): ManageTableSettings => {
	const [hiddenColumns, setHiddenColumns] = useState(
		() => new Set(window.CODE_SNIPPETS_MANAGE?.hiddenColumns ?? [])
	)
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
				new Set(Array.from(screenOptions.querySelectorAll<HTMLInputElement>(
					'.hide-column-tog:not(:checked)'
				))
					.map(toggle => toggle.value))
			)

			setTruncateRowValues(
				screenOptions.querySelector<HTMLInputElement>(
					'#snippets-table-truncate-row-values'
				)?.checked ?? true
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

const NoItemsMessage = () => {
	const { currentType, currentTag, searchQuery } = useSnippetsFilters()
	const emptyMessage = currentType
		? __("You don't have snippets of this type yet.", 'code-snippets')
		: __("You don't have any snippets yet.", 'code-snippets')

	return searchQuery || currentTag
		? <>
			{__('No snippets were found matching the current search query.', 'code-snippets')}
			{__(' Please enter a new query or use the "Clear Filters" button above.', 'code-snippets')}
		</>
		: <>
			{emptyMessage}{' '}
			<a href={buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type: currentType })}>
				{__('Add a new snippet.', 'code-snippets')}
			</a>
		</>
}

const getRowClassName = (
	snippet: Snippet,
	activeByCondition: Map<Snippet['id'], Snippet[]>
): string =>
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
	setSnippetView: (view: SnippetView) => void
	snippets: Snippet[]
	actions: ListTableAction<SnippetsTableAction>[]
	doAction: (action: SnippetsTableAction | undefined, selected: Set<Snippet['id']>) => Promise<void>
	extraTableNav: (which: 'top' | 'bottom') => ReactNode
	hiddenColumns: Set<string>
	truncateRowValues: boolean
}

const SnippetsView: React.FC<SnippetsViewProps> = ({
	snippetView,
	setSnippetView,
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
	const endTableNav = (which: 'top' | 'bottom') =>
		'top' === which
			? <SnippetViewToggle snippetView={snippetView} setSnippetView={setSnippetView} />
			: null

	return 'card' === snippetView
		? <SnippetsCardGrid
			snippets={snippets}
			actions={actions}
			doAction={doAction}
			itemsPerPage={itemsPerPage}
			extraTableNav={extraTableNav}
			endTableNav={endTableNav}
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
			selectAllControl
			endTableNav={endTableNav}
			rowClassName={snippet => getRowClassName(snippet, activeByCondition)}
			noItems={<NoItemsMessage />}
			beforeTable={<SearchResultsIndicator />}
		/>
}

export interface SnippetsListTableProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

export const SnippetsListTable: React.FC<SnippetsListTableProps> = ({
	snippetView,
	setSnippetView
}) => {
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
		<SnippetsTableNavigation which={which} visibleSnippets={snippetsByStatus.get('all') ?? []} />

	return (
		<>
			<SnippetsTableToolbar />

			<div className="snippets-list-view">
				<SnippetsView
					snippetView={snippetView}
					setSnippetView={setSnippetView}
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
