import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { SubmitButton } from '../SubmitButton'
import type { ListTableBulkAction, ListTableNavProps } from './ListTable'
import type { Key } from 'react'

interface BulkActionsProps<K extends Key> extends Required<Pick<TableNavProps<K>, 'which' | 'actions'>> {
	applyAction: (action: ListTableBulkAction<K>) => void
}

const BulkActions = <K extends Key>({ which, actions, applyAction }: BulkActionsProps<K>) => {
	const [selectedAction, setSelectedAction] = useState<ListTableBulkAction<K>>()

	const actionsMap: Map<string, ListTableBulkAction<K>> = useMemo(
		() => new Map(
			actions
				.flatMap(actionOrGroup =>
					'actions' in actionOrGroup ? actionOrGroup.actions : [actionOrGroup])
				.map(action => [action.name, action])
		), [actions])

	return (
		<div className="alignleft actions bulkactions">
			<label htmlFor={`bulk-action-selector-${which}`} className="screen-reader-text">
				{/* translators: Hidden accessibility text. */}
				{__('Select bulk action', 'code-snippets')}
			</label>

			<select
				name={`action${'bottom' === which ? '-2' : ''}`}
				id={`bulk-action-selector-${which}`}
				onChange={event => {
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

			<SubmitButton
				id={`doaction${'bottom' === which ? '-2' : ''}`}
				name="bulk_action"
				text={__('Apply', 'code-snippets')}
				className="action"
				onClick={event => {
					event.preventDefault()

					if (selectedAction) {
						applyAction(selectedAction)
						setSelectedAction(undefined)
					}
				}}
			/>
		</div>
	)
}

export interface TableNavProps<K extends Key> extends ListTableNavProps<K> {
	which: 'top' | 'bottom'
	hasItems: boolean
	selected: Set<K>
}

export const TableNav = <K extends Key>({ which, actions, hasItems, extraTableNav, selected }: TableNavProps<K>) =>
	extraTableNav || hasItems && actions
		? <div className={`tablenav ${which}`}>

			{hasItems && actions
				? <BulkActions
					which={which}
					actions={actions}
					applyAction={action => {
						action.apply(selected)
					}}
				/>
				: null}

			{extraTableNav?.(which)}

			{/* TODO pagination */}

			<br className="clear" />
		</div>
		: null
