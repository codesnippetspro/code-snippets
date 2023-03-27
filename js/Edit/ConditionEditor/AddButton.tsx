import { __ } from '@wordpress/i18n'
import React, { Dispatch, SetStateAction } from 'react'
import { ConditionGroups, ConditionGroup } from '../../types/Condition'
import { Snippet } from '../../types/Snippet'

const getNextIndex = (items: Record<PropertyKey, unknown>) => {
	const keys = items ? Object.keys(items) : []
	return 1 + (keys.length ? Math.max(...keys.map(Number).filter(value => !Number.isNaN(value))) : 0)
}

const createGroup = (groups: ConditionGroups | undefined = {}): ConditionGroups => ({
	...groups,
	[getNextIndex(groups)]: { 1: { subject: '', operator: 'eq', object: '' } }
})

const createCondition = (conditions: ConditionGroup | undefined = {}): ConditionGroup => ({
	...conditions,
	[getNextIndex(conditions)]: { subject: '', operator: 'eq', object: '' }
})

export interface AddButtonProps {
	group: keyof ConditionGroups
	insertLabel: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const AddButton: React.FC<AddButtonProps> = ({ group, insertLabel, setSnippet }) =>
	<button
		className="button condition-add-button"
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => ({
				...previous,
				conditions: {
					...previous.conditions,
					[group]: createCondition(previous.conditions?.[group])
				}
			}))
		}}
	>
		<span className="dashicons dashicons-insert"></span>
		<span>{insertLabel}</span>
	</button>

export interface AddGroupButtonProps {
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const AddGroupButton: React.FC<AddGroupButtonProps> = ({ setSnippet }) =>
	<button
		className="button condition-add-button condition-add-group-button"
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => ({ ...previous, conditions: createGroup(previous.conditions) }))
		}}
	>
		<span className="dashicons dashicons-insert"></span>
		<span>{__('Add condition group', 'code-snippets')}</span>
	</button>

export interface AddConditionButtonProps extends AddGroupButtonProps {
	groupId: string
}

export const AddConditionButton: React.FC<AddConditionButtonProps> = ({ groupId, setSnippet }) =>
	<div className="condition-add-row-button">
		<button
			className="button condition-add-button"
			onClick={event => {
				event.preventDefault()
				setSnippet(previous => ({
					...previous,
					conditions: {
						...previous.conditions,
						[groupId]: createCondition(previous.conditions?.[groupId])
					}
				}))
			}}
		>
			<span className="dashicons dashicons-insert"></span>
			<span>{__('AND', 'code-snippets')}</span>
		</button>
	</div>
