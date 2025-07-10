import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { handleUnknownError } from '../../../utils/errors'
import { Button } from '../../common/Button'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { isNetworkAdmin } from '../../../utils/general'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import type { Snippet } from '../../../types/Snippet'
import type { ButtonProps } from '../../common/Button'

interface SubmitButtonProps extends ButtonProps {
	inlineButtons?: boolean
}

const SaveChangesButton: React.FC<SubmitButtonProps> = ({ inlineButtons, ...props }) =>
	<Button
		name="save_snippet"
		type="submit"
		small={inlineButtons}
		title={inlineButtons ? __('Save Snippet', 'code-snippets') : undefined}
		{...props}
	>
		{__('Save Changes', 'code-snippets')}
	</Button>

const SaveAndExecuteButton: React.FC<SubmitButtonProps> = ({ inlineButtons, ...props }) =>
	<Button
		name="save_snippet_execute"
		title={inlineButtons ? __('Save Snippet and Execute Once', 'code-snippets') : undefined}
		{...props}
	>
		{inlineButtons
			? __('Execute Once', 'code-snippets')
			: __('Save Changes and Execute Once', 'code-snippets')}
	</Button>

const DeactivateButton: React.FC<SubmitButtonProps> = ({ inlineButtons, ...props }) =>
	<Button
		name="save_snippet_deactivate"
		title={inlineButtons ? __('Save Snippet and Deactivate', 'code-snippets') : undefined}
		{...props}
	>
		{inlineButtons
			? __('Deactivate', 'code-snippets')
			: __('Save Changes and Deactivate', 'code-snippets')}
	</Button>

const ActivateButton: React.FC<SubmitButtonProps> = ({ inlineButtons, ...props }) =>
	<Button
		name="save_snippet_activate"
		title={inlineButtons ? __('Save Snippet and Activate', 'code-snippets') : undefined}
		{...props}
	>
		{inlineButtons
			? __('Activate', 'code-snippets')
			: __('Save Changes and Activate', 'code-snippets')}
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
	inlineButtons,
	primaryActivate
}) => {
	const commonProps: SubmitButtonProps = { small: inlineButtons, type: 'submit', disabled, inlineButtons }

	switch (true) {
		case snippet.shared_network && isNetworkAdmin():
			return null

		case 'single-use' === snippet.scope:
			return <SaveAndExecuteButton onClick={onActivate} {...commonProps} />

		case snippet.active:
			return <DeactivateButton onClick={onDeactivate} {...commonProps} />

		default:
		case !snippet.active:
			return <ActivateButton onClick={onActivate} primary={primaryActivate} {...commonProps} />
	}
}

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

const shouldActivateByDefault = (snippet: Snippet, inlineButtons?: boolean): boolean =>
	!inlineButtons
	&& !!window.CODE_SNIPPETS_EDIT?.activateByDefault
	&& !snippet.active
	&& 'single-use' !== snippet.scope
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

export interface SubmitButtonsProps {
	inlineButtons?: boolean
}

export const SubmitButton: React.FC<SubmitButtonsProps> = ({ inlineButtons }) => {
	const { snippet, isWorking, submitSnippet, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()
	const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false)
	const [submitAction, setSubmitAction] = useState<VoidFunction>()
	const validationWarning = validateSnippet(snippet)
	const activateByDefault = shouldActivateByDefault(snippet, inlineButtons)

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
		{activateByDefault ? null
			: <SaveChangesButton
				primary={!inlineButtons}
				onClick={() => handleSubmit(submitSnippet)}
				disabled={isWorking}
				inlineButtons={inlineButtons}
			/>}

		<ActivateOrDeactivateButton
			snippet={snippet}
			disabled={isWorking}
			inlineButtons={inlineButtons}
			primaryActivate={activateByDefault}
			onActivate={() => handleSubmit(submitAndActivateSnippet)}
			onDeactivate={() => handleSubmit(submitAndDeactivateSnippet)}
		/>

		{activateByDefault
			? <SaveChangesButton
				onClick={() => handleSubmit(submitSnippet)}
				disabled={isWorking}
				inlineButtons={inlineButtons}
			/> : null}

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
