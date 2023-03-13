import React from 'react'
import { __ } from '@wordpress/i18n'
import { ActionButton } from '../common/ActionButton'
import { Snippet } from '../types/Snippet'
import { isNetworkAdmin } from '../utils/general'
import { deleteSnippet, downloadSnippet, exportSnippet, saveSnippet, saveSnippetActivate, saveSnippetDeactivate } from './actions'

export interface ActionButtonsProps {
	snippet: Snippet
}

const SubmitButton: React.FC<ActionButtonsProps> = ({ snippet }) => {
	const canActivate = !snippet.shared_network || !isNetworkAdmin()
	const activateByDefault = canActivate && window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope

	return <>
		{activateByDefault ? '' :
			<ActionButton
				primary
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => saveSnippet(snippet)}
			/>}

		{'single-use' === snippet.scope ?
			<ActionButton
				name="save_snippet_execute"
				text={__('Save Changes and Execute Once', 'code-snippets')}
				onClick={() => saveSnippetActivate(snippet)}
			/> :

			canActivate ?
				snippet.active ?
					<ActionButton
						name="save_snippet_deactivate"
						text={__('Save Changes and Deactivate', 'code-snippets')}
						onClick={() => saveSnippetDeactivate(snippet)}
					/> :
					<ActionButton
						primary={activateByDefault}
						name="save_snippet_activate"
						text={__('Save Changes and Activate', 'code-snippets')}
						onClick={() => saveSnippetActivate(snippet)}
					/> : ''}

		{activateByDefault ?
			<ActionButton
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => saveSnippet(snippet)}
			/> : ''}
	</>
}

export const ActionButtons: React.FC<ActionButtonsProps> = ({ snippet }) =>
	<p className="submit">
		<SubmitButton snippet={snippet} />

		{snippet.active ?
			<>
				{window.CODE_SNIPPETS_EDIT?.enableDownloads ?
					<ActionButton
						name="download_snippet"
						text={__('Download', 'code-snippets')}
						onClick={() => downloadSnippet(snippet)}
					/> : ''}

				<ActionButton
					name="export_snippet"
					text={__('Export', 'code-snippets')}
					onClick={() => exportSnippet(snippet)}
				/>

				<ActionButton
					name="delete_snippet"
					text={__('Delete', 'code-snippets')}
					onClick={() =>
						confirm([
							__('You are about to permanently delete this snippet.', 'code-snippets'),
							__("'Cancel' to stop, 'OK' to delete.", 'code-snippets')
						].join('\n')) && deleteSnippet(snippet)
					}
				/>
			</> : ''}
	</p>
