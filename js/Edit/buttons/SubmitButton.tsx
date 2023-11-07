import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Button, ButtonProps } from '../../common/Button'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { Snippet } from '../../types/Snippet'
import { isNetworkAdmin } from '../../utils/general'
import { useSnippetForm } from '../SnippetForm/context'

const SaveChangesButton: React.FC<ButtonProps> = ({ ...props }) =>
	<Button
		name="save_snippet"
		{...props}
	>
		{__('Save Changes', 'code-snippets')}
	</Button>

interface ActivateButtonProps {
	snippet: Snippet
	disabled: boolean
	onActivate: VoidFunction
	onDeactivate: VoidFunction
	primaryActivate: boolean
}

const ActivateButton: React.FC<ActivateButtonProps> = ({
	snippet,
	disabled,
	onActivate,
	onDeactivate,
	primaryActivate
}) =>
	<>
		{'single-use' === snippet.scope ?
			<Button
				type="submit"
				name="save_snippet_execute"
				onClick={onActivate}
				disabled={disabled}
			>
				{__('Save Changes and Execute Once', 'code-snippets')}
			</Button> :

			!snippet.shared_network || !isNetworkAdmin() ?
				snippet.active ?
					<Button
						name="save_snippet_deactivate"
						onClick={onDeactivate}
						disabled={disabled}
					>
						{__('Save Changes and Deactivate', 'code-snippets')}
					</Button> :
					<Button
						type={primaryActivate ? 'submit' : 'button'}
						primary={primaryActivate}
						name="save_snippet_activate"
						onClick={onActivate}
						disabled={disabled}
					>
						{__('Save Changes and Activate', 'code-snippets')}
					</Button> : ''}
	</>

const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingCode = '' === snippet.code.trim()
	const missingTitle = '' === snippet.name.trim()

	switch (true) {
		case missingCode && missingTitle:
			return __('This snippet has no code or title.', 'code-snippets')

		case missingCode:
			return __('This snippet has no snippet code.', 'code-snippets')

		case missingTitle:
			return __('This snippet has no title.', 'code-snippets')

		default:
			return undefined
	}
}

export const SubmitButton: React.FC = () => {
	const { snippet, isWorking, submitSnippet, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()
	const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false)
	const [submitAction, setSubmitAction] = useState<VoidFunction>()

	const validationWarning = validateSnippet(snippet)
	const activateByDefault = !!window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope &&
		(!snippet.shared_network || !isNetworkAdmin())

	const onSubmit = (submitAction: VoidFunction) => {
		if (validationWarning) {
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

	const saveChangesButtonProps: ButtonProps = {
		disabled: isWorking,
		onClick: () => onSubmit(submitSnippet)
	}

	return <>
		{activateByDefault ? null : <SaveChangesButton primary type="submit" {...saveChangesButtonProps} />}

		<ActivateButton
			snippet={snippet}
			disabled={isWorking}
			primaryActivate={activateByDefault}
			onActivate={() => onSubmit(submitAndActivateSnippet)}
			onDeactivate={() => onSubmit(submitAndDeactivateSnippet)}
		/>

		{activateByDefault ? <SaveChangesButton {...saveChangesButtonProps} /> : null}

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
			<p>{`${validationWarning} ${__('Continue?', 'code-snippets')}`}</p>
		</ConfirmDialog>
	</>
}
