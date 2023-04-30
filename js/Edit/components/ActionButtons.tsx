import { Spinner } from '@wordpress/components'
import React, { MouseEvent, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { Snippet } from '../../types/Snippet'
import { isNetworkAdmin } from '../../utils/general'
import { SnippetActionsProps, SnippetActionsValue, useSnippetActions } from '../actions'

export interface SubmitButtonProps {
	actions: SnippetActionsValue
	snippet: Snippet
	isWorking: boolean
}

// eslint-disable-next-line max-lines-per-function
const SubmitButton: React.FC<SubmitButtonProps> = ({ actions, snippet, isWorking }) => {
	const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false)
	const [submitAction, setSubmitAction] = useState<() => void>()

	const canActivate = !snippet.shared_network || !isNetworkAdmin()
	const activateByDefault = canActivate && window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope

	const missingCode = '' === snippet.code.trim()
	const missingTitle = '' === snippet.name.trim()

	const doSubmit = (event: MouseEvent<HTMLButtonElement>, submitAction: () => void) => {
		if (missingCode || missingTitle) {
			setIsConfirmDialogOpen(true)
			setSubmitAction(() => submitAction)
		} else {
			submitAction()
		}
	}

	const closeDialog = () => {
		setIsConfirmDialogOpen(false)
		setSubmitAction(undefined)
	}

	return <>
		{activateByDefault ? '' :
			<ActionButton
				primary
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={event => doSubmit(event, () => actions.submit(snippet))}
				disabled={isWorking}
			/>}

		{'single-use' === snippet.scope ?
			<ActionButton
				name="save_snippet_execute"
				text={__('Save Changes and Execute Once', 'code-snippets')}
				onClick={event => doSubmit(event, () => actions.submitAndActivate(snippet, true))}
				disabled={isWorking}
			/> :

			canActivate ?
				snippet.active ?
					<ActionButton
						name="save_snippet_deactivate"
						text={__('Save Changes and Deactivate', 'code-snippets')}
						onClick={event => doSubmit(event, () => actions.submitAndActivate(snippet, false))}
						disabled={isWorking}
					/> :
					<ActionButton
						primary={activateByDefault}
						name="save_snippet_activate"
						text={__('Save Changes and Activate', 'code-snippets')}
						onClick={event => doSubmit(event, () => actions.submitAndActivate(snippet, true))}
						disabled={isWorking}
					/> : ''}

		{activateByDefault ?
			<ActionButton
				name="save_snippet"
				text={__('Save Changes', 'code-snippets')}
				onClick={event => doSubmit(event, () => actions.submit(snippet))}
				disabled={isWorking}
			/> : ''}

		<ConfirmDialog
			open={isConfirmDialogOpen}
			title={__('Snippet incomplete', 'code-snippets')}
			confirmLabel={__('Continue', 'code-snippets')}
			onCancel={closeDialog}
			onConfirm={() => {
				submitAction?.()
				closeDialog()
			}}
		>
			<p>
				{missingCode && missingTitle ? __('This snippet has no code or title. Continue?', 'code-snippets') :
					missingCode ? __('This snippet has no snippet code. Continue?', 'code-snippets') :
						missingTitle ? __('This snippet has no title. Continue?', 'code-snippets') : ''}
			</p>
		</ConfirmDialog>
	</>
}

export interface ActionButtonProps extends SnippetActionsProps {
	snippet: Snippet
	isWorking: boolean
}

export const ActionButtons: React.FC<ActionButtonProps> = ({ snippet, isWorking, ...actionsProps }) => {
	const actions = useSnippetActions({ ...actionsProps })
	const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false)

	return (
		<p className="submit">
			<SubmitButton actions={actions} snippet={snippet} isWorking={isWorking} />

			{snippet.id ?
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
						onClick={() => setIsDeleteDialogOpen(true)}
						disabled={isWorking}
					/>
				</> : ''}

			{isWorking ? <Spinner /> : ''}

			<ConfirmDialog
				open={isDeleteDialogOpen}
				title={__('Permanently delete?', 'code-snippets')}
				confirmLabel={__('Delete', 'code-snippet')}
				confirmButtonClassName="is-destructive"
				onCancel={() => setIsDeleteDialogOpen(false)}
				onConfirm={() => {
					setIsDeleteDialogOpen(false)
					actions.delete(snippet)
				}}
			>
				<p>
					{__('You are about to permanently delete this snippet.', 'code-snippets')}{' '}
					{__('Are you sure?', 'code-snippets')}
				</p>
				<p><strong>{__('This action cannot be undone.', 'code-snippets')}</strong></p>
			</ConfirmDialog>
		</p>
	)
}
