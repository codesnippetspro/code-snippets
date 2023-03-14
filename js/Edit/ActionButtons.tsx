import React from 'react'
import { __ } from '@wordpress/i18n'
import { ActionButton } from '../common/ActionButton'
import { SnippetInputProps } from '../types/SnippetInputProps'
import { useSnippetsAPI } from '../utils/api'
import { isNetworkAdmin } from '../utils/general'
import { saveSnippet, saveAndActivateSnippet, deleteSnippet, exportSnippet, exportSnippetCode } from './actions'

const SubmitButton: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) => {
	const api = useSnippetsAPI(setSnippet)
	const canActivate = !snippet.shared_network || !isNetworkAdmin()
	const activateByDefault = canActivate && window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope

	return <>
		{activateByDefault ? '' :
			<ActionButton
				primary
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => saveSnippet(snippet, api)}
			/>}

		{'single-use' === snippet.scope ?
			<ActionButton
				name="save_snippet_execute"
				text={__('Save Changes and Execute Once', 'code-snippets')}
				onClick={() => saveAndActivateSnippet(snippet, api, true)}
			/> :

			canActivate ?
				snippet.active ?
					<ActionButton
						name="save_snippet_deactivate"
						text={__('Save Changes and Deactivate', 'code-snippets')}
						onClick={() => saveAndActivateSnippet(snippet, api, false)}
					/> :
					<ActionButton
						primary={activateByDefault}
						name="save_snippet_activate"
						text={__('Save Changes and Activate', 'code-snippets')}
						onClick={() => saveAndActivateSnippet(snippet, api, true)}
					/> : ''}

		{activateByDefault ?
			<ActionButton
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => saveSnippet(snippet, api)}
			/> : ''}
	</>
}

export const ActionButtons: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) => {
	const api = useSnippetsAPI(setSnippet)

	return (
		<p className="submit">
			<SubmitButton snippet={snippet} setSnippet={setSnippet} />

			{snippet.active ?
				<>
					<ActionButton
						name="export_snippet"
						text={__('Export', 'code-snippets')}
						onClick={() => exportSnippet(snippet, api)}
					/>

					{window.CODE_SNIPPETS_EDIT?.enableDownloads ?
						<ActionButton
							name="export_snippet_code"
							text={__('Export Code', 'code-snippets')}
							onClick={() => exportSnippetCode(snippet, api)}
						/> : ''}

					<ActionButton
						name="delete_snippet"
						text={__('Delete', 'code-snippets')}
						onClick={() => deleteSnippet(snippet, api)}
					/>
				</> : ''}
		</p>
	)
}
