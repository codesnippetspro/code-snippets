import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { handleUnknownError } from '../../../utils/errors'
import { SubmitButton } from '../SubmitButton'
import { TablePagination } from './TablePagination'
import type { TablePaginationProps } from './TablePagination'
import type { ListTableBulkAction, ListTableNavProps } from './ListTable'
import type { Key } from 'react'

interface BulkActionSelectProps<K extends Key> extends Required<Pick<TableNavProps<K>, 'which' | 'actions'>> {
	selectedActionName: string
	setSelectedActionName: (value: string) => void
	setSelectedAction: (action: ListTableBulkAction<K> | undefined) => void
}

const BulkActionSelect = <K extends Key>({
	which,
	actions,
	selectedActionName,
	setSelectedActionName,
	setSelectedAction
}: BulkActionSelectProps<K>) => {
	const actionsMap: Map<string, ListTableBulkAction<K>> = useMemo(
		() => new Map(
			actions
				.flatMap(actionOrGroup =>
					'actions' in actionOrGroup ? actionOrGroup.actions : [actionOrGroup])
				.map(action => [action.name, action])
		), [actions])

	return (
		<select
			name={`action${'bottom' === which ? '-2' : ''}`}
			id={`bulk-action-selector-${which}`}
			value={selectedActionName}
			onChange={event => {
				setSelectedActionName(event.target.value)
				setSelectedAction(actionsMap.get(event.target.value))
			}}
		>
			<option value="-1">{__('Bulk actions', 'code-snippets')}</option>

			{actions.map(actionOrGroup =>
				'actions' in actionOrGroup
					? <optgroup key={actionOrGroup.name} label={actionOrGroup.name}>
						{actionOrGroup.actions.map(action =>
							<option key={action.name} value={action.name}>{action.name}</option>)}
					</optgroup>
					: <option key={actionOrGroup.name} value={actionOrGroup.name}>{actionOrGroup.name}</option>)}
		</select>
	)
}

interface BulkActionsProps<K extends Key> extends Required<Pick<TableNavProps<K>, 'which' | 'actions'>> {
	applyAction: (action: ListTableBulkAction<K>) => Promise<void>
	disabled?: boolean
}

const BulkActions = <K extends Key>({ which, actions, applyAction, disabled }: BulkActionsProps<K>) => {
	const [selectedAction, setSelectedAction] = useState<ListTableBulkAction<K>>()
	const [selectedActionName, setSelectedActionName] = useState('-1')
	const [isPerformingAction, setIsPerformingAction] = useState(false)

	return (
		<div className="alignleft actions bulkactions">
			<label htmlFor={`bulk-action-selector-${which}`} className="screen-reader-text">
				{/* translators: Hidden accessibility text. */}
				{__('Select bulk action', 'code-snippets')}
			</label>

			<BulkActionSelect
				{...{ which, actions, selectedActionName, setSelectedActionName, setSelectedAction }}
			/>

			<SubmitButton
				id={`doaction${'bottom' === which ? '-2' : ''}`}
				name="bulk_action"
				text={__('Apply', 'code-snippets')}
				className="action"
				disabled={disabled ?? isPerformingAction}
				onClick={event => {
					event.preventDefault()

					if (selectedAction) {
						setIsPerformingAction(true)
						applyAction(selectedAction)
							.catch(handleUnknownError)
							.finally(() => {
								setIsPerformingAction(false)
							})
					}
				}}
			/>

			{isPerformingAction ? <Spinner /> : null}
		</div>
	)
}

export interface TableNavProps<K extends Key> extends ListTableNavProps<K>, Omit<TablePaginationProps, 'totalPages'> {
	which: 'top' | 'bottom'
	selected: Set<K>
	totalItems: number
	totalPages: number | undefined
}

export const TableNav = <K extends Key>({
	which,
	actions,
	selected,
	totalItems,
	totalPages = 0,
	extraTableNav,
	...paginationProps
}: TableNavProps<K>) =>
	extraTableNav || 0 < totalItems && actions
		? <div className={`tablenav ${which}`}>

			{0 < totalItems && actions && (
				<BulkActions
					which={which}
					actions={actions}
					disabled={paginationProps.disabled}
					applyAction={action => action.apply(selected)}
				/>)}

			{extraTableNav?.(which)}
			{0 < totalPages && <TablePagination {...{ totalPages, totalItems, which, ...paginationProps }} />}

			<br className="clear" />
		</div>
		: null
