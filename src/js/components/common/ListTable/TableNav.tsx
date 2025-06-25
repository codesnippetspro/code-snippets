import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { handleUnknownError } from '../../../utils/errors'
import { SubmitButton } from '../SubmitButton'
import type { ListTableBulkAction, ListTableNavProps } from './ListTable'
import type { Key } from 'react'

interface BulkActionSelectProps<K extends Key> extends Required<Pick<TableNavProps<K>, 'which' | 'actions'>> {
	setSelectedAction: (action: ListTableBulkAction<K> | undefined) => void
}

const BulkActionSelect = <K extends Key>({ which, actions, setSelectedAction }: BulkActionSelectProps<K>) => {
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
	)
}

interface BulkActionsProps<K extends Key> extends Required<Pick<TableNavProps<K>, 'which' | 'actions'>> {
	applyAction: (action: ListTableBulkAction<K>) => Promise<void>
}

const BulkActions = <K extends Key>({ which, actions, applyAction }: BulkActionsProps<K>) => {
	const [selectedAction, setSelectedAction] = useState<ListTableBulkAction<K>>()
	const [isPerformingAction, setIsPerformingAction] = useState(false)

	return (
		<div className="alignleft actions bulkactions">
			<label htmlFor={`bulk-action-selector-${which}`} className="screen-reader-text">
				{/* translators: Hidden accessibility text. */}
				{__('Select bulk action', 'code-snippets')}
			</label>

			<BulkActionSelect {...{ which, actions, setSelectedAction }} />

			<SubmitButton
				id={`doaction${'bottom' === which ? '-2' : ''}`}
				name="bulk_action"
				text={__('Apply', 'code-snippets')}
				className="action"
				disabled={isPerformingAction}
				onClick={event => {
					event.preventDefault()

					if (selectedAction) {
						setIsPerformingAction(true)
						applyAction(selectedAction)
							.catch(handleUnknownError)
							.finally(() => {
								setSelectedAction(undefined)
								setIsPerformingAction(false)
							})
					}
				}}
			/>

			{isPerformingAction ? <Spinner /> : null}
		</div>
	)
}

export interface TableNavProps<K extends Key> extends ListTableNavProps<K> {
	which: 'top' | 'bottom'
	hasItems: boolean
	selected: Set<K>
}

export const TableNav = <K extends Key>({
	which,
	actions,
	hasItems,
	selected,
	extraTableNav
}: TableNavProps<K>) =>
	extraTableNav || hasItems && actions
		? <div className={`tablenav ${which}`}>

			{hasItems && actions
				? <BulkActions
					which={which}
					actions={actions}
					applyAction={action => action.apply(selected)}
				/>
				: null}

			{extraTableNav?.(which)}

			{/* TODO pagination */}

			<br className="clear" />
		</div>
		: null
