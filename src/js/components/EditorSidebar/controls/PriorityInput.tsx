import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { Tooltip } from '../../common/Tooltip'

export const PriorityInput = () => {
	const { snippet, isReadOnly, setSnippet } = useSnippetForm()

	return (
		<div className="snippet-priority inline-form-field">
			<h4>
				<label htmlFor="snippet-priority">
					{__('Priority', 'code-snippets')}
				</label>
			</h4>

			<Tooltip block end>
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
