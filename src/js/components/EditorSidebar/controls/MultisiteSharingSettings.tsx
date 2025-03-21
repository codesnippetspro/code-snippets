import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const MultisiteSharingSettings: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return (
		<div>
			<h4>
				<label htmlFor="snippet_sharing">
					{__('Share with Subsites', 'code-snippets')}
				</label>
			</h4>

			<div className="help-tooltip">
				<span className="dashicons dashicons-editor-help"></span>
				<span className="help-tooltip-text">
					{__('Instead of running on every site, allow this snippet to be activated on individual sites on the network.', 'code-snippets')}
				</span>
			</div>

			<input
				id="snippet_sharing"
				name="snippet_sharing"
				type="checkbox"
				className="switch"
				checked={true === snippet.shared_network}
				disabled={isReadOnly}
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
