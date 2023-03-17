import React, { Dispatch, SetStateAction } from 'react'
import { ConditionGroups, ConditionGroup } from '../../types/Condition'
import { Snippet } from '../../types/Snippet'

const getNextIndex = (items: Record<PropertyKey, unknown>) => {
	const keys = items ? Object.keys(items) : []
	return 1 + (keys.length ? Math.max(...keys.map(Number).filter(value => !Number.isNaN(value))) : 0)
}

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
