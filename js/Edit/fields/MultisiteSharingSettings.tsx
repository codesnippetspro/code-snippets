import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../SnippetForm/context'

export const MultisiteSharingSettings: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return <>
		<h2 className="screen-reader-text">{__('Sharing Settings', 'code-snippets')}</h2>
		<p className="snippet-sharing-setting">
			<label htmlFor="snippet_sharing">
				<input
					id="snippet_sharing"
					name="snippet_sharing"
					type="checkbox"
					checked={!!snippet.shared_network}
					disabled={isReadOnly}
					onChange={event =>
						setSnippet(previous => ({ ...previous, shared_network: event.target.checked }))}
				/>
				{__('Allow this snippet to be activated on individual sites on the network', 'code-snippets')}
			</label>
		</p>
	</>
}
