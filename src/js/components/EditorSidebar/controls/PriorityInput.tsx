import React from 'react'
import { __, _x } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const PriorityInput = () => {
	const { snippet, isReadOnly, setSnippet } = useSnippetForm()

	return (
		<div className="snippet-priority">
			<h4>
				<label htmlFor="snippet-priority">
					{__('Priority', 'code-snippets')}
				</label>
			</h4>

			<div className="help-tooltip">
				<span className="dashicons dashicons-editor-help"></span>
				<div className="help-tooltip-text">
					{__('Snippets with a lower priority number will run before those with a higher number.', 'code-snippets')}
				</div>
			</div>

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
