import React, { ReactNode, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { ConfirmDialog } from '../components/common/ConfirmDialog'
import type { Snippet } from '../types/Snippet'
import { handleUnknownError } from '../utils/errors'
import { isCondition } from '../utils/snippets/snippets'
import { useSnippetForm } from './useSnippetForm'

export enum SubmitSnippetAction {
	SAVE = "save_snippet",
	SAVE_AND_ACTIVATE = "save_snippet_activate",
	SAVE_AND_EXECUTE = "save_snippet_execute",
	SAVE_AND_DEACTIVATE = 'save_snippet_deactivate'
}

const SUBMIT_ACTION_DELTA: Record<SubmitSnippetAction, Partial<Snippet>> = {
	[SubmitSnippetAction.SAVE]: {},
	[SubmitSnippetAction.SAVE_AND_ACTIVATE]: { active: true },
	[SubmitSnippetAction.SAVE_AND_DEACTIVATE]: { active: false },
	[SubmitSnippetAction.SAVE_AND_EXECUTE]: { active: true }
}

const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingTitle = '' === snippet.name.trim()

	const missingCode = isCondition(snippet)
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

export interface UseSnippetFormSubmit {
	validateAndSubmit: (action?: SubmitSnippetAction) => void
	SubmitConfirmationDialog: () => ReactNode
}

export const useSnippetFormSubmit = (): UseSnippetFormSubmit => {
	const { snippet, saveSnippet } = useSnippetForm()
	const [submitAction, setSubmitAction] = useState<SubmitSnippetAction>(SubmitSnippetAction.SAVE)
	const [validationWarning, setValidationWarning] = useState<string | undefined>()

	const submitSnippet = (action: SubmitSnippetAction) => {
		saveSnippet(SUBMIT_ACTION_DELTA[action]).then(() => undefined).catch(handleUnknownError)
	}

	const validateAndSubmit = (action: SubmitSnippetAction = SubmitSnippetAction.SAVE) => {
		const validationWarning = validateSnippet(snippet)

		if (validationWarning) {
			setValidationWarning(validationWarning)
			setSubmitAction(action)
		} else {
			submitSnippet(action)
		}
	}

	const SubmitConfirmationDialog = () =>
		<ConfirmDialog
			open={validationWarning !== undefined}
			title={__('Snippet incomplete', 'code-snippets')}
			confirmLabel={__('Continue', 'code-snippets')}
			onCancel={() => {
				setValidationWarning(undefined)
				setSubmitAction(SubmitSnippetAction.SAVE)
			}}
			onConfirm={() => {
				submitSnippet(submitAction)
				setValidationWarning(undefined)
				setSubmitAction(SubmitSnippetAction.SAVE)
			}}
		>
			<p>{`${validationWarning} ${__('Continue?', 'code-snippets')}`}</p>
		</ConfirmDialog>

	return { validateAndSubmit, SubmitConfirmationDialog }
}
