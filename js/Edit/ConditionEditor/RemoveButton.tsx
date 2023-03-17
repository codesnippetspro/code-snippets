import { __ } from '@wordpress/i18n'
import React, { Dispatch, SetStateAction } from 'react'
import { ConditionGroups, ConditionGroup } from '../../types/Condition'
import { Snippet } from '../../types/Snippet'

const removeCondition = (conditions: ConditionGroup | undefined, conditionId: string): ConditionGroup | undefined => {
	if (conditions && conditions[conditionId]) {
		const { [conditionId]: condition, ...remaining } = conditions
		return remaining
	} else {
		return conditions
	}
}

export interface RemoveButtonProps {
	group: keyof ConditionGroups
	conditionId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const RemoveButton: React.FC<RemoveButtonProps> = ({ group, conditionId, setSnippet }) =>
	<button
		className="button condition-remove-button"
		title={__('Remove this condition from the group.', 'code-snippets')}
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => ({
				...previous,
				conditions: {
					...previous.conditions,
					[group]: removeCondition(previous.conditions?.[group], conditionId)
				}
			}))
		}}
	>
		<span className="dashicons dashicons-trash"></span>
	</button>
