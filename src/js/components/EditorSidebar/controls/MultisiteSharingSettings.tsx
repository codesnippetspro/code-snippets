import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { Tooltip } from '../../common/Tooltip'

export const MultisiteSharingSettings: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return (
		<div className="inline-form-field activation-switch-container">
			<h4>
				{__('Share with Subsites', 'code-snippets')}
			</h4>

			<Tooltip inline start>
				{__('Instead of running on every site, allow this snippet to be activated on individual sites on the network.', 'code-snippets')}
			</Tooltip>

			<label>
				{snippet.shared_network
					? __('Enabled', 'code-snippets')
					: __('Disabled', 'code-snippets')}

				<input
					id="snippet_sharing"
					name="snippet_sharing"
					type="checkbox"
					className="switch"
					checked={!!snippet.shared_network}
					disabled={isReadOnly}
					onChange={event =>
						setSnippet(previous => ({
							...previous,
							active: false,
							shared_network: event.target.checked
						}))}
				/>
			</label>
		</div>
	)
}
