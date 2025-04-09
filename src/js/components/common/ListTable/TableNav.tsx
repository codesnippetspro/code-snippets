import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { SubmitButton } from '../SubmitButton'
import type { ListTableNavProps } from './ListTable'
import type { ChangeEventHandler, Key} from 'react'

type BulkActionsProps<K extends Key> = Required<Pick<TableNavProps<K>, 'which' | 'actions' | 'handleBulkAction' | 'selected'>>

const BulkActions = <K extends Key>({ which, actions, handleBulkAction, selected: selectedItems }: BulkActionsProps<K>) => {
	const [selectedAction, setSelectedAction] = useState<string>()

	const handleChange: ChangeEventHandler<HTMLSelectElement> = event => {
		setSelectedAction(event.target.value)
	}

	return (
		<div className="alignleft actions bulkactions">
			<label htmlFor={`bulk-action-selector-${which}`} className="screen-reader-text">
				{/* translators: Hidden accessibility text. */}
				{__('Select bulk action', 'code-snippets')}
			</label>

			<select name={`action${'bottom' === which ? '-2' : ''}`} id={`bulk-action-selector-${which}`} onChange={handleChange}>
				<option value="-1">{__('Bulk actions', 'code-snippets')}</option>
				{Object.entries(actions).map(([key, value]) =>
					'object' === typeof value
						? <optgroup key={key} label={key}>
							{Object.entries(value).map(([name, title]) =>
								<option key={name} value={name} className={'edit' === name ? 'hide-if-no-js' : undefined}>{title}</option>)}
						</optgroup>
						: <option key={key} value={key} className={'edit' === key ? 'hide-if-no-js' : undefined}>{value}</option>)}
			</select>

			<SubmitButton
				id={`doaction${'bottom' === which ? '-2' : ''}`}
				name="bulk_action"
				text={__('Apply', 'code-snippets')}
				className="action"
				primary={false}
				large={false}
				wrap={false}
				onClick={event => {
					event.preventDefault()

					if (selectedAction) {
						handleBulkAction(selectedAction, selectedItems)
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

export const TableNav = <K extends Key>({ which, actions, hasItems, handleBulkAction, extraTableNav, selected }: TableNavProps<K>) => {
	const showBulkActions = hasItems && actions && handleBulkAction && 0 < Object.keys(actions).length

	return extraTableNav || showBulkActions
		? <div className={`tablenav ${which}`}>

			{showBulkActions && <BulkActions {...{ which, actions, handleBulkAction, selected }} />}
			{extraTableNav?.(which)}

			{/* TODO pagination */}

			<br className="clear" />
		</div>
		: null
}
