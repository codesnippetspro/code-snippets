import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../../hooks/useSnippetForm'
import { SubmitSnippetAction, useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'

export const ActivationSwitch = () => {
	const { snippet, isWorking } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()

	return (
		<div>
			<h4>
				<label htmlFor="activation-switch">
					{__('Is Active', 'code-snippets')}
				</label>
			</h4>

			<input
				id="activation-switch"
				type="checkbox"
				checked={snippet.active}
				disabled={isWorking || !!snippet.shared_network}
				className="switch"
				title={snippet.active
					? __('Deactivate', 'code-snippets')
					: __('Activate', 'code-snippets')}
				onChange={() => {
					submitSnippet(snippet.active
						? SubmitSnippetAction.SAVE_AND_DEACTIVATE
						: SubmitSnippetAction.SAVE_AND_ACTIVATE)
						.then(() => undefined)
						.catch(handleUnknownError)
				}}
			/>
		</div>
	)
}
