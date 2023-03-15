import { Spinner } from '@wordpress/components'
import React from 'react'
import { __ } from '@wordpress/i18n'
import { ActionButton } from '../common/ActionButton'
import { Snippet } from '../types/Snippet'
import { isNetworkAdmin } from '../utils/general'
import { SnippetActionsProps, SnippetActionsValue, useSnippetActions } from './actions'

export interface SubmitButtonProps {
	actions: SnippetActionsValue
	snippet: Snippet
	isWorking: boolean
}

const SubmitButton: React.FC<SubmitButtonProps> = ({ actions, snippet, isWorking }) => {
	const canActivate = !snippet.shared_network || !isNetworkAdmin()
	const activateByDefault = canActivate && window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope

	return <>
		{activateByDefault ? '' :
			<ActionButton
				primary
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => actions.submit(snippet)}
				disabled={isWorking}
			/>}

		{'single-use' === snippet.scope ?
			<ActionButton
				name="save_snippet_execute"
				text={__('Save Changes and Execute Once', 'code-snippets')}
				onClick={() => actions.submitAndActivate(snippet, true)}
				disabled={isWorking}
			/> :

			canActivate ?
				snippet.active ?
					<ActionButton
						name="save_snippet_deactivate"
						text={__('Save Changes and Deactivate', 'code-snippets')}
						onClick={() => actions.submitAndActivate(snippet, false)}
						disabled={isWorking}
					/> :
					<ActionButton
						primary={activateByDefault}
						name="save_snippet_activate"
						text={__('Save Changes and Activate', 'code-snippets')}
						onClick={() => actions.submitAndActivate(snippet, true)}
						disabled={isWorking}
					/> : ''}

		{activateByDefault ?
			<ActionButton
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={() => actions.submit(snippet)}
				disabled={isWorking}
			/> : ''}
	</>
}

export interface ActionButtonProps extends SnippetActionsProps {
	snippet: Snippet
	isWorking: boolean
}

export const ActionButtons: React.FC<ActionButtonProps> = ({ snippet, isWorking, ...actionsProps }) => {
	const actions = useSnippetActions({ ...actionsProps })

	return (
		<p className="submit">
			<SubmitButton actions={actions} snippet={snippet} isWorking={isWorking} />

			{snippet.active ?
				<>
					<ActionButton
						name="export_snippet"
						text={__('Export', 'code-snippets')}
						onClick={() => actions.export(snippet)}
						disabled={isWorking}
					/>

					{window.CODE_SNIPPETS_EDIT?.enableDownloads ?
						<ActionButton
							name="export_snippet_code"
							text={__('Export Code', 'code-snippets')}
							onClick={() => actions.exportCode(snippet)}
							disabled={isWorking}
						/> : ''}

					<ActionButton
						name="delete_snippet"
						text={__('Delete', 'code-snippets')}
						onClick={() => actions.delete(snippet)}
						disabled={isWorking}
					/>
				</> : ''}

			{isWorking ? <Spinner /> : ''}
		</p>
	)
}
