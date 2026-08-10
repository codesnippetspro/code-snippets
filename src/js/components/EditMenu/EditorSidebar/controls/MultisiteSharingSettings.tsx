import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { Tooltip } from '../../../common/Tooltip'

export const MultisiteSharingSettings: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()
	const sharingId = useId()

	return (
		<div className="inline-form-field activation-switch-container">
			<label htmlFor={sharingId} id="snippet-sharing-label">
				{__('Share with Subsites', 'code-snippets')}
			</label>

			<Tooltip inline start>
				{__('Instead of running on every site, allow this snippet to be activated on individual sites on the network.', 'code-snippets')}
			</Tooltip>

			<span className="sharing-status-text">
				{snippet.shared_network
					? __('Enabled', 'code-snippets')
					: __('Disabled', 'code-snippets')}
			</span>

			<input
				id={sharingId}
				name="snippet_sharing"
				type="checkbox"
				className="switch"
				checked={!!snippet.shared_network}
				disabled={isReadOnly}
				aria-labelledby="snippet-sharing-label"
				onChange={event =>
					setSnippet(previous => ({
						...previous,
						active: false,
						shared_network: event.target.checked
					}))}
			/>
		</div>
	)
}
