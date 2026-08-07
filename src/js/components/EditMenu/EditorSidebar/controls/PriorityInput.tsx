import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { Tooltip } from '../../../common/Tooltip'

export const PriorityInput = () => {
	const { snippet, isReadOnly, setSnippet } = useSnippetForm()
	const priorityId = useId()

	return (
		<div className="snippet-priority inline-form-field">
			<label htmlFor={priorityId} id="snippet-priority-label">
				{__('Priority', 'code-snippets')}
			</label>

			<Tooltip block end className="priority-input-tooltip">
				{__('Snippets with a lower priority number will run before those with a higher number.', 'code-snippets')}
			</Tooltip>

			<input
				type="number"
				id={priorityId}
				name="snippet_priority"
				value={snippet.priority}
				disabled={isReadOnly}
				aria-labelledby="snippet-priority-label"
				onChange={event => setSnippet(previous => ({
					...previous,
					priority: parseInt(event.target.value, 10)
				}))}
			/>
		</div>
	)
}
