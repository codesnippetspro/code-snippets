import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { handleUnknownError } from '../../../utils/errors'
import { SubmitButton } from '../SubmitButton'
import { TablePagination } from './TablePagination'
import type { ListTableAction, ListTableNavProps } from './ListTable'
import type { TablePaginationProps } from './TablePagination'
import type { Dispatch, Key, MouseEventHandler, PropsWithChildren, SetStateAction } from 'react'

const isBulkAction = <A extends string>(value: string, actions: ListTableAction<A>[]): value is A =>
	actions.some(action => action.key === value)

interface BulkActionSelectOptionsProps<A extends string> {
	actions: ListTableAction<A>[]
}

const BulkActionSelectOptions = <A extends string>({ actions }: BulkActionSelectOptionsProps<A>) => {
	const [options, optionGroups] = useMemo(() => {
		const ungroupedActions: ListTableAction<A>[] = []
		const groupedActions = new Map<string, ListTableAction<A>[]>()

		for (const action of actions) {
			if (action.group === undefined) {
				ungroupedActions.push(action)
			} else {
				groupedActions.set(action.group, [...groupedActions.get(action.group) ?? [], action])
			}
		}

		return [ungroupedActions, Array.from(groupedActions.entries())]
	}, [actions])

	return (
		<>
			{options.map(action => <option key={action.key} value={action.key}>{action.label}</option>)}

			{optionGroups.map(([groupLabel, groupActions]) =>
				<optgroup key={groupLabel} label={groupLabel}>
					{groupActions.map(action =>
						<option key={action.label} value={action.label}>{action.label}</option>)}
				</optgroup>)}
		</>
	)
}

interface BulkActionSelectProps<A extends string> {
	which: 'top' | 'bottom'
	actions: ListTableAction<A>[]
	selectedAction: A | undefined
	setSelectedAction: Dispatch<SetStateAction<A | undefined>>
}

const BulkActionSelect = <A extends string>({ which, actions, selectedAction, setSelectedAction }: BulkActionSelectProps<A>) =>
	<select
		name={`action${'bottom' === which ? '-2' : ''}`}
		id={`bulk-action-selector-${which}`}
		value={selectedAction}
		onChange={({ target: { value } }) => {
			if (!value || '-1' === value) {
				setSelectedAction(undefined)
			} else if (isBulkAction(value, actions)) {
				setSelectedAction(value)
			}
		}}
	>
		<option value="-1">{__('Bulk actions', 'code-snippets')}</option>
		<BulkActionSelectOptions actions={actions} />
	</select>

interface BulkActionsProps<K extends Key, A extends string> extends Required<Pick<TableNavProps<K, A>, 'which' | 'actions' | 'doAction'>> {
	onActionSuccess?: () => void
	disabled?: boolean
	selected: Set<K>
}

const BulkActions = function BulkActions<K extends Key, A extends string>({
	which,
	actions,
	selected,
	doAction,
	onActionSuccess,
	disabled
}: BulkActionsProps<K, A>) {
	const [selectedAction, setSelectedAction] = useState<A>()
	const [isPerformingAction, setIsPerformingAction] = useState(false)

	const handleSubmit: MouseEventHandler<HTMLInputElement> = event => {
		event.preventDefault()

		if (selectedAction) {
			setIsPerformingAction(true)
			doAction(selectedAction, selected)
				.then(() => {
					onActionSuccess?.()
				})
				.catch(handleUnknownError)
				.finally(() => setIsPerformingAction(false))
		}
	}

	return (
		<div className="alignleft actions bulkactions">
			<label htmlFor={`bulk-action-selector-${which}`} className="screen-reader-text">
				{/* translators: Hidden accessibility text. */}
				{__('Select bulk action', 'code-snippets')}
			</label>

			<BulkActionSelect {...{ which, actions, selectedAction, setSelectedAction }} />

			<SubmitButton
				id={`doaction${'bottom' === which ? '-2' : ''}`}
				name="bulk_action"
				text={__('Apply', 'code-snippets')}
				className="action"
				disabled={!!disabled || isPerformingAction || !selectedAction}
				onClick={handleSubmit}
			/>

			{isPerformingAction ? <Spinner /> : null}
		</div>
	)
}

interface SelectAllControlProps<K extends Key> {
	visibleKeys: K[]
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

const SelectAllControl = <K extends Key>({ visibleKeys, selected, setSelected }: SelectAllControlProps<K>) =>
	<label className="tablenav-select-all">
		<input
			type="checkbox"
			checked={0 < visibleKeys.length && visibleKeys.every(key => selected.has(key))}
			disabled={0 === visibleKeys.length}
			aria-label={__('Select all items', 'code-snippets')}
			onChange={event => {
				setSelected(previous => {
					const updated = new Set(previous)
					visibleKeys.forEach(key => {
						if (event.target.checked) {
							updated.add(key)
						} else {
							updated.delete(key)
						}
					})
					return updated
				})
			}}
		/>
		{__('Select all', 'code-snippets')}
	</label>

export interface TableNavProps<K extends Key, A extends string> extends TableNavigationProps<K, A> {
	which: 'top' | 'bottom'
}

export const TableNav = <K extends Key, A extends string>({
	which,
	actions,
	doAction,
	selected,
	setSelected,
	totalItems,
	totalPages = 0,
	extraTableNav,
	selectAll,
	endTableNav,
	visibleKeys,
	...paginationProps
}: TableNavProps<K, A>) =>
	('bottom' === which ? 0 < totalItems && actions || 0 < totalPages : extraTableNav || 0 < totalItems && actions)
		? <div className={`tablenav ${which}`}>

			{0 < totalItems && actions && doAction && (
				<BulkActions
					which={which}
					actions={actions}
					doAction={doAction}
					disabled={paginationProps.disabled}
					selected={selected}
					onActionSuccess={() => setSelected(new Set())}
				/>)}

			{'top' === which && selectAll && visibleKeys
				? <SelectAllControl {...{ visibleKeys, selected, setSelected }} />
				: null}

			{'top' === which ? extraTableNav?.(which) : null}

			{0 < totalPages || 'top' === which && endTableNav
				? <div className="tablenav-end-group">
					{0 < totalPages && <TablePagination {...{ totalPages, totalItems, which, ...paginationProps }} />}
					{'top' === which ? endTableNav : null}
				</div>
				: null}

			<br className="clear" />
		</div>
		: null

export interface TableNavigationProps<K extends Key, A extends string> extends ListTableNavProps<K, A>,
	Omit<TablePaginationProps, 'totalPages' | 'which'> {
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
	totalItems: number
	totalPages: number | undefined
	visibleKeys?: K[]
}

export const TableNavigation = <K extends Key, A extends string>({
	children,
	...props
}: PropsWithChildren<TableNavigationProps<K, A>>) =>
	<>
		<TableNav which="top" {...props} />
		{children}
		<TableNav which="bottom" {...props} />
	</>
