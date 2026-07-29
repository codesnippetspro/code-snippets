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
	label: string
}

const BulkActionSelect = <A extends string>({
	which,
	actions,
	selectedAction,
	setSelectedAction,
	label
}: BulkActionSelectProps<A>) =>
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
		<option value="-1">{label}</option>
		<BulkActionSelectOptions actions={actions} />
	</select>

interface BulkActionsProps<K extends Key, A extends string> extends Required<Pick<TableNavProps<K, A>, 'which' | 'actions' | 'doAction'>> {
	onActionSuccess?: () => void
	disabled?: boolean
	selected: Set<K>
	selectLabel?: string
}

const BulkActions = function BulkActions<K extends Key, A extends string>({
	which,
	actions,
	selected,
	doAction,
	onActionSuccess,
	disabled,
	selectLabel
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

			<BulkActionSelect
				{...{ which, actions, selectedAction, setSelectedAction }}
				label={selectLabel ?? __('Bulk actions', 'code-snippets')}
			/>

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

export interface SelectAllControlProps<K extends Key> {
	keys: K[]
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

export const SelectAllControl = <K extends Key>({
	keys,
	selected,
	setSelected
}: SelectAllControlProps<K>) =>
	<label className="tablenav-select-all">
		<input
			type="checkbox"
			checked={0 < keys.length && keys.every(key => selected.has(key))}
			disabled={0 === keys.length}
			aria-label={__('Select all items', 'code-snippets')}
			onChange={event => {
				const { checked } = event.target

				setSelected(previous => {
					const updated = new Set(previous)
					keys.forEach(key => checked ? updated.add(key) : updated.delete(key))
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
	endTableNav,
	selectAllKeys,
	bulkSelectLabel,
	...paginationProps
}: TableNavProps<K, A>) => {
	const isTop = 'top' === which
	const hasBulkActions = 0 < totalItems && Boolean(actions)

	return isTop && Boolean(extraTableNav ?? endTableNav) || hasBulkActions || 0 < totalPages
		? <div className={`tablenav ${which}`}>

			{0 < totalItems && actions && doAction && (
				<BulkActions
					which={which}
					actions={actions}
					doAction={doAction}
					disabled={paginationProps.disabled}
					selected={selected}
					selectLabel={bulkSelectLabel}
					onActionSuccess={() => setSelected(new Set())}
				/>)}

			{isTop && selectAllKeys
				? <SelectAllControl keys={selectAllKeys} selected={selected} setSelected={setSelected} />
				: null}

			{isTop ? extraTableNav?.(which) : null}

			{0 < totalPages || isTop && endTableNav
				? <div className="tablenav-end-group">
					{0 < totalPages &&
						<TablePagination {...{ totalPages, totalItems, which, ...paginationProps }} />}
					{isTop ? endTableNav?.(which) : null}
				</div>
				: null}

			<br className="clear" />
		</div>
		: null
}

export interface TableNavigationProps<K extends Key, A extends string> extends ListTableNavProps<K, A>,
	Omit<TablePaginationProps, 'totalPages' | 'which'> {
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
	totalItems: number
	totalPages: number | undefined
	selectAllKeys?: K[]
	bulkSelectLabel?: string
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
