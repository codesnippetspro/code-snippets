import React from 'react'
import { __ } from '@wordpress/i18n'
import { SubmitSnippetAction, useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

export const LockControl: React.FC = () => {
	const { snippet, setSnippet, isWorking } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()

	const handleToggle = () => {
		const newLockedStatus = !snippet.locked

		// Create the updated snippet object immediately
		const updatedSnippet = {
			...snippet,
			locked: newLockedStatus
		}

		// Update local state for immediate UI response
		setSnippet(updatedSnippet)

		// Submit to the server using the override to prevent stale state issues
		submitSnippet(SubmitSnippetAction.SAVE, updatedSnippet)
			.then(() => undefined)
			.catch(handleUnknownError)
	}

	return (
		<div className="inline-form-field lock-control-container">
			<h4>{__('Lock Snippet', 'code-snippets')}</h4>

			<label>
				{snippet.locked
					? __('Locked', 'code-snippets')
					: __('Unlocked', 'code-snippets')}

				<input
					id="snippet-lock"
					type="checkbox"
					checked={snippet.locked}
					disabled={isWorking}
					className="switch"
					onChange={handleToggle}
				/>
			</label>
			<p className="description">
				{__('Prevent accidental changes or deletion.', 'code-snippets')}
			</p>
		</div>
	)
}
