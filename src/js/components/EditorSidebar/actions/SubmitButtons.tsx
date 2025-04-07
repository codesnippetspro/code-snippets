import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { handleUnknownError } from '../../../utils/errors'
import { getSnippetType, isCondition } from '../../../utils/snippets'
import { Button } from '../../common/Button'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { isNetworkAdmin } from '../../../utils/screen'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import type { Snippet } from '../../../types/Snippet'
import type { ButtonProps } from '../../common/Button'

const SaveChangesButton: React.FC<ButtonProps> = ({ ...props }) =>
	<Button
		name="save_snippet"
		type="submit"
		large
		{...props}
	>
		{__('Save Snippet', 'code-snippets')}
	</Button>

interface ActivateOrDeactivateButtonProps {
	snippet: Snippet
	onActivate: VoidFunction
	onDeactivate: VoidFunction
	primaryActivate: boolean
	inlineButtons?: boolean
	disabled: boolean
}

const ActivateOrDeactivateButton: React.FC<ActivateOrDeactivateButtonProps> = ({
	snippet,
	disabled,
	onActivate,
	onDeactivate,
	primaryActivate
}) => {
	switch (true) {
		case isCondition(snippet) || snippet.shared_network && isNetworkAdmin():
			return null

		case 'single-use' === snippet.scope:
			return (
				<Button name="save_snippet_execute" onClick={onActivate} type="submit" disabled={disabled} large>
					{__('Save and Execute Once', 'code-snippets')}
				</Button>
			)

		case snippet.active:
			return (
				<Button name="save_snippet_deactivate" onClick={onDeactivate} type="submit" disabled={disabled} large>
					{__('Save and Deactivate', 'code-snippets')}
				</Button>
			)

		default:
		case !snippet.active:
			return (
				<Button name="save_snippet_activate" type="submit" onClick={onActivate} primary={primaryActivate} disabled={disabled} large>
					{__('Save and Activate', 'code-snippets')}
				</Button>
			)
	}
}

const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingTitle = '' === snippet.name.trim()

	const missingCode = 'cond' === getSnippetType(snippet)
		? !snippet.conditions
		: '' === snippet.code.trim()

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
const shouldActivateByDefault = (snippet: Snippet): boolean =>
	!!window.CODE_SNIPPETS_EDIT?.activateByDefault
	&& !snippet.active && 'single-use' !== snippet.scope
	&& (!snippet.shared_network || !isNetworkAdmin())

interface SubmitConfirmDialogProps {
	isOpen: boolean
	onClose: VoidFunction
	onSubmit?: VoidFunction
	validationWarning?: string
}

const SubmitConfirmDialog: React.FC<SubmitConfirmDialogProps> = ({ isOpen, onClose, onSubmit, validationWarning }) =>
	<ConfirmDialog
		open={isOpen}
		title={__('Snippet incomplete', 'code-snippets')}
		confirmLabel={__('Continue', 'code-snippets')}
		onCancel={onClose}
		onConfirm={() => {
			onSubmit?.()
			onClose()
		}}
	>
		<p>{`${validationWarning} ${__('Continue?', 'code-snippets')}`}</p>
	</ConfirmDialog>

export const SubmitButton: React.FC = () => {
	const { snippet, isWorking, submitSnippet, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()
	const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false)
	const [submitAction, setSubmitAction] = useState<VoidFunction>()
	const validationWarning = validateSnippet(snippet)
	const activateByDefault = shouldActivateByDefault(snippet)

	const handleSubmit = (submitAction: () => Promise<Snippet | undefined>): void => {
		if (validationWarning) {
			setIsConfirmDialogOpen(true)
			setSubmitAction(() => submitAction)
		} else {
			submitAction()
				.then(() => undefined)
				.catch(handleUnknownError)
		}
	}

	return <>
		{activateByDefault
			? <SaveChangesButton
				primary={!activateByDefault}
				onClick={() => handleSubmit(submitSnippet)}
				disabled={isWorking}
			/> : null}

		<ActivateOrDeactivateButton
			snippet={snippet}
			disabled={isWorking}
			primaryActivate={activateByDefault}
			onActivate={() => handleSubmit(submitAndActivateSnippet)}
			onDeactivate={() => handleSubmit(submitAndDeactivateSnippet)}
		/>

		{activateByDefault ? null
			: <SaveChangesButton
				primary
				onClick={() => handleSubmit(submitSnippet)}
				disabled={isWorking}
			/>}

		<SubmitConfirmDialog
			isOpen={isConfirmDialogOpen}
			validationWarning={validationWarning}
			onSubmit={submitAction}
			onClose={() => {
				setIsConfirmDialogOpen(false)
				setSubmitAction(undefined)
			}}
		/>
	</>
}
