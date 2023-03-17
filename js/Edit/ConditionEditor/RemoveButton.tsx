import { __ } from '@wordpress/i18n'
import React, { Dispatch, SetStateAction } from 'react'
import { Snippet } from '../../types/Snippet'

const removeCondition = (snippet: Snippet, groupId: string, conditionId: string): Snippet => {
	if (!snippet.conditions?.[groupId][conditionId]) {
		return snippet
	}

	const { [groupId]: group, ...conditions } = snippet.conditions
	const { [conditionId]: condition, ...remaining } = group

	return {
		...snippet,
		conditions: {
			...conditions,
			...0 === Object.keys(remaining).length ? undefined : { [groupId]: remaining }
		}
	}
}

export interface RemoveButtonProps {
	groupId: string
	conditionId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const RemoveButton: React.FC<RemoveButtonProps> = ({ groupId, conditionId, setSnippet }) =>
	<button
		className="button condition-remove-button"
		title={__('Remove this condition from the group.', 'code-snippets')}
		onClick={event => {
			event.preventDefault()
			setSnippet(previous => removeCondition(previous, groupId, conditionId))
		}}
	>
		<span className="dashicons dashicons-trash"></span>
	</button>
