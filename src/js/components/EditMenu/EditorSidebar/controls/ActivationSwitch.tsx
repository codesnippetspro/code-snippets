import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { SubmitSnippetAction, useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'

export const ActivationSwitch = () => {
	const { snippet, isWorking } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
	const activationSwitchId = useId()

	return (
		<div className="inline-form-field activation-switch-container">
			<label htmlFor={activationSwitchId} id="snippet-activation-switch-label">
				{__('Status', 'code-snippets')}
			</label>

			<span className="status-text">
				{snippet.active
					? __('Active', 'code-snippets')
					: __('Inactive', 'code-snippets')}
			</span>

			<input
				id={activationSwitchId}
				type="checkbox"
				checked={snippet.active}
				disabled={isWorking || !!snippet.shared_network}
				className="switch"
				aria-labelledby="snippet-activation-switch-label"
				onChange={() => {
					submitSnippet(
						{ id: snippet.id, network: snippet.network, active: !snippet.active },
						snippet.active ? SubmitSnippetAction.SAVE_AND_DEACTIVATE : SubmitSnippetAction.SAVE_AND_ACTIVATE
					)
						.then(() => undefined)
						.catch(handleUnknownError)
				}}
			/>
		</div>
	)
}
