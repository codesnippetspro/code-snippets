import { __, _n, sprintf } from '@wordpress/i18n'
import React, { useEffect, useMemo, useState } from 'react'
import classnames from 'classnames'
import { getSnippetType, isSnippetActive } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { ListTable } from '../../common/ListTable'
import { DismissibleNotice } from '../../common/Notice'
import { SnippetViewToggle } from '../../common/SnippetViewToggle'
import { SnippetsCardGrid } from './SnippetsCardGrid'
import { SnippetsTableNavigation, SnippetsTableToolbar } from './SnippetsTableControls'
import { SearchResultsIndicator } from './SnippetsTableSearch'
import { getTableColumns } from './TableColumns'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { INDEX_STATUS, useSnippetsFilters } from './WithSnippetsTableFilters'
import { BULK_ACTIONS, TRASHED_BULK_ACTIONS, useApplyBulkAction } from './useApplyBulkAction'
import type { ListTableAction, ListTableSortDirection } from '../../common/ListTable'
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
	const itemsPerPage = Number(window.CODE_SNIPPETS_MANAGE?.snippetsPerPage) || undefined
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
			initialSort={getInitialSort()}
			className={classnames({ 'truncate-row-values': truncateRowValues })}
			columns={columns}
			actions={actions}
			doAction={doAction}
			itemsPerPage={itemsPerPage}
			extraTableNav={extraTableNav}
			selectAllControl
			endTableNav={endTableNav}
			rowClassName={snippet => getRowClassName(snippet, activeByCondition)}
			noItems={<NoItemsMessage />}
			beforeTable={<SearchResultsIndicator />}
		/>
}

/**
 * The "Snippets List Order" setting names a default ordering for the table,
 * which sorting is now expressed through columns. Map one to the other so the
 * setting still decides how the list opens; clicking a heading overrides it for
 * the rest of the visit, as it does for any other starting order.
 */
const LIST_ORDER_SORTS: Record<string, { columnId: string, direction: ListTableSortDirection }> = {
	'priority-asc': { columnId: 'priority', direction: 'asc' },
	'name-asc': { columnId: 'name', direction: 'asc' },
	'name-desc': { columnId: 'name', direction: 'desc' },
	'modified-desc': { columnId: 'date', direction: 'desc' },
	'modified-asc': { columnId: 'date', direction: 'asc' }
}

const getInitialSort = () =>
	LIST_ORDER_SORTS[window.CODE_SNIPPETS_MANAGE?.listOrder ?? ''] ?? undefined

export interface SnippetsListTableProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
}

type ApplyBulkAction = ReturnType<typeof useApplyBulkAction>

/**
 * Hold a permanent bulk delete until it is confirmed, and count what it deleted.
 */
const useConfirmedBulkDelete = (applyBulkAction: ApplyBulkAction) => {
	const [pendingDelete, setPendingDelete] = useState<Set<Snippet['id']>>()
	const [deletedCount, setDeletedCount] = useState<number>()

	const doAction = async (action: SnippetsTableAction | undefined, selected: Set<Snippet['id']>): Promise<void> => {
		if ('delete' === action) {
			setPendingDelete(selected)
		} else {
			await applyBulkAction(action, selected)
		}
	}

	const confirmDelete = () => {
		const selected = pendingDelete
		setPendingDelete(undefined)

		if (selected) {
			void applyBulkAction('delete', selected).then(count => setDeletedCount(count ?? 0))
		}
	}

	return {
		doAction,
		pendingDelete,
		cancelDelete: () => setPendingDelete(undefined),
		confirmDelete,
		deletedCount,
		dismissDeleted: () => setDeletedCount(undefined)
	}
}

interface BulkDeleteFeedbackProps {
	pendingDelete: Set<Snippet['id']> | undefined
	cancelDelete: VoidFunction
	confirmDelete: VoidFunction
	deletedCount: number | undefined
	dismissDeleted: VoidFunction
}

const BulkDeleteFeedback: React.FC<BulkDeleteFeedbackProps> = ({
	pendingDelete,
	cancelDelete,
	confirmDelete,
	deletedCount,
	dismissDeleted
}) =>
	<>
		{undefined !== deletedCount &&
			<DismissibleNotice type="success" onDismiss={dismissDeleted}>
				<p>
					{sprintf(
						// translators: %d: number of snippets.
						_n('%d snippet permanently deleted.', '%d snippets permanently deleted.', deletedCount, 'code-snippets'),
						deletedCount
					)}
				</p>
			</DismissibleNotice>}

		<ConfirmDialog
			open={undefined !== pendingDelete}
			title={__('Are you sure?', 'code-snippets')}
			confirmLabel={__('Delete', 'code-snippets')}
			confirmButtonClassName="is-destructive"
			onCancel={cancelDelete}
			onConfirm={confirmDelete}
		>
			<p>
				{sprintf(
					// translators: %d: number of snippets.
					_n('%d snippet will be permanently deleted.', '%d snippets will be permanently deleted.', pendingDelete?.size ?? 0, 'code-snippets'),
					pendingDelete?.size ?? 0
				)}
			</p>
			<p>{__('This action cannot be undone.', 'code-snippets')}</p>
		</ConfirmDialog>
	</>

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
	const { doAction, pendingDelete, cancelDelete, confirmDelete, deletedCount, dismissDeleted } = useConfirmedBulkDelete(applyBulkAction)

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

			<BulkDeleteFeedback
				pendingDelete={pendingDelete}
				cancelDelete={cancelDelete}
				confirmDelete={confirmDelete}
				deletedCount={deletedCount}
				dismissDeleted={dismissDeleted}
			/>

			<div className="snippets-list-view">
				<SnippetsView
					snippetView={snippetView}
					setSnippetView={setSnippetView}
					snippets={currentSnippets}
					actions={'trashed' === currentStatus ? TRASHED_BULK_ACTIONS : BULK_ACTIONS}
					doAction={doAction}
					extraTableNav={extraTableNav}
					hiddenColumns={hiddenColumns}
					truncateRowValues={truncateRowValues}
				/>
			</div>
		</>
	)
}
