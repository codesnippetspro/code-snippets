import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import type { Snippet } from '../../../types/Snippet'

export interface SnippetPriorityInputProps {
	snippet: Snippet
}

/**
 * Inline number field for updating a snippet's priority, saving on blur or
 * form submission. Shared between the snippets list table, card kebab menu,
 * and preview modal.
 */
export const SnippetPriorityInput: React.FC<SnippetPriorityInputProps> = ({ snippet }) => {
	const [value, setValue] = useState(snippet.priority)
	const snippetsAPI = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const handleUpdate = () => {
		// The kebab menu can focus this input on open, so a blur without a
		// real change must not trigger a write action.
		if (Number.isNaN(value) || value === snippet.priority) {
			setValue(snippet.priority)
			return
		}

		snippetsAPI.update({ ...snippet, priority: value })
			.then(response => {
				if (response.id === snippet.id) {
					setValue(response.priority)
				}
			})
			.then(refreshSnippetsList)
			.catch(handleUnknownError)
	}

	return (
		<form onSubmit={event => {
			event.preventDefault()
			handleUpdate()
		}}>
			<input
				id={`snippet-${snippet.id}-priority`}
				type="number"
				className="snippet-priority"
				value={value}
				step="1"
				onBlur={handleUpdate}
				aria-label={__('Snippet priority', 'code-snippets')}
				onChange={event => setValue(Number(event.target.value))}
				disabled={snippet.locked || snippet.trashed}
			/>
		</form>
	)
}
