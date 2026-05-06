import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { Tooltip } from '../../../common/Tooltip'

export const PriorityInput = () => {
	const { snippet, isReadOnly, setSnippet } = useSnippetForm()

	return (
		<div className="snippet-priority inline-form-field">
			<label htmlFor="snippet-priority">
				{__('Priority', 'code-snippets')}
			</label>

			<Tooltip block end className="priority-input-tooltip">
				{__('Snippets with a lower priority number will run before those with a higher number.', 'code-snippets')}
			</Tooltip>

			<input
				type="number"
				id="snippet-priority"
				name="snippet_priority"
				value={snippet.priority}
				disabled={isReadOnly}
				onChange={event => setSnippet(previous => ({
					...previous,
					priority: parseInt(event.target.value, 10)
				}))}
			/>
		</div>
	)
}
