import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { SubmitSnippetAction, useSubmitSnippet } from '../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../utils/errors'

export const ActivationSwitch = () => {
	const { snippet, isWorking } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()

	return (
		<div className="inline-form-field activation-switch-container">
			<h4>{__('Status')}</h4>

			<label>
				{snippet.active
					? __('Active', 'code-snippets')
					: __('Inactive', 'code-snippets')}

				<input
					id="activation-switch"
					type="checkbox"
					checked={snippet.active}
					disabled={isWorking || !!snippet.shared_network}
					className="switch"
					onChange={() => {
						submitSnippet(snippet.active
							? SubmitSnippetAction.SAVE_AND_DEACTIVATE
							: SubmitSnippetAction.SAVE_AND_ACTIVATE)
							.then(() => undefined)
							.catch(handleUnknownError)
					}}
				/>
			</label>
		</div>
	)
}
