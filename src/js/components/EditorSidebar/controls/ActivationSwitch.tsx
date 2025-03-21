import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { handleUnknownError } from '../../../utils/errors'

export const ActivationSwitch = () => {
	const { snippet, isWorking, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()

	return (
		<div>
			<h4>
				<label htmlFor="snippet-activation-switch">
					{__('Is Active', 'code-snippets')}
				</label>
			</h4>

			<input
				id="snippet-activation-switch"
				type="checkbox"
				disabled={isWorking || !!snippet.shared_network}
				className="switch snippet-activation-switch"
				title={snippet.active ?
					__('Deactivate', 'code-snippets') :
					__('Activate', 'code-snippets')}
				onChange={() => {
					(snippet.active ?
						submitAndDeactivateSnippet() :
						submitAndActivateSnippet())
						.then(() => undefined)
						.catch(handleUnknownError)
				}}
			/>
		</div>
	)
}
