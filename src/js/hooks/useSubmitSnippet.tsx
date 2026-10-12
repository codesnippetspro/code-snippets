import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import React, { useCallback } from 'react'
import { describeRequestError, isUnconfirmedRequest } from '../utils/errors'
import { useSnippetForm } from '../components/EditMenu/SnippetForm/WithSnippetFormContext'
import { createSnippetObject, isCondition } from '../utils/snippets/snippets'
import { buildUrl } from '../utils/urls'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { Snippet } from '../types/Snippet'
import type { ScreenNotice } from '../types/ScreenNotice'

const snippetMessages = {
	addNew: __('Create New Snippet', 'code-snippets'),
	edit: __('Edit Snippet', 'code-snippets'),
	created: __('Snippet <strong>created</strong>.', 'code-snippets'),
	updated: __('Snippet <strong>updated</strong>.', 'code-snippets'),
	createdActivated: __('Snippet <strong>created</strong> and <strong>activated</strong>.', 'code-snippets'),
	updatedActivated: __('Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets'),
	updatedDeactivated: __('Snippet <strong>updated</strong> and <strong>deactivated</strong>'),
	updatedExecuted: __('Snippet <strong>updated</strong> and <strong>executed</strong>.', 'code-snippets'),
	failedCreate: __('Could not create snippet.', 'code-snippets'),
	failedUpdate: __('Could not update snippet.', 'code-snippets'),
} as const

const conditionCreated = __('Condition <strong>created</strong>.', 'code-snippets')
const conditionUpdated = __('Condition <strong>updated</strong>.', 'code-snippets')

/** A save that did not produce a snippet, and whether its outcome is actually known. */
interface SubmitFailure {
	message?: string
	unconfirmed: boolean
}

const isSubmitFailure = (result: Snippet | SubmitFailure): result is SubmitFailure =>
	'unconfirmed' in result

interface SubmitFailureNotice {
	failure: SubmitFailure
	messages: typeof snippetMessages
	isUpdate: boolean
}

/**
 * Word a save that did not return a snippet.
 *
 * A request that never received a response says nothing about whether the write
 * happened, so it is reported on its own rather than behind a heading stating
 * the snippet was not saved.
 */
const describeSubmitFailure = ({ failure, messages, isUpdate }: SubmitFailureNotice): string =>
	failure.unconfirmed
		? failure.message ?? ''
		: [
			isUpdate ? messages.failedUpdate : messages.failedCreate,
			failure.message ?? __('The server did not send a valid response.', 'code-snippets')
		].filter(Boolean).join(' ')

const conditionMessages: typeof snippetMessages = {
	addNew: __('Create New Condition', 'code-snippets'),
	edit: __('Edit Condition', 'code-snippets'),
	created: conditionCreated,
	updated: conditionUpdated,
	createdActivated: conditionCreated,
	updatedActivated: conditionUpdated,
	updatedDeactivated: conditionUpdated,
	updatedExecuted: conditionUpdated,
	failedCreate: __('Could not create condition.', 'code-snippets'),
	failedUpdate: __('Could not update condition.', 'code-snippets')
}

export enum SubmitSnippetAction {
	SAVE = 'save_snippet',
	SAVE_AND_ACTIVATE = 'save_snippet_activate',
	SAVE_AND_EXECUTE = 'save_snippet_execute',
	SAVE_AND_DEACTIVATE = 'save_snippet_deactivate'
}

const getSuccessNotice = (
	request: Partial<Snippet>,
	response: Snippet,
	action: SubmitSnippetAction = SubmitSnippetAction.SAVE
): string => {
	const messages = 'condition' === request.scope ? conditionMessages : snippetMessages
	const wasCreated = 0 === request.id

	switch (action) {
		case SubmitSnippetAction.SAVE:
			return wasCreated ? messages.created : messages.updated

		case SubmitSnippetAction.SAVE_AND_EXECUTE:
			return messages.updatedExecuted

		case SubmitSnippetAction.SAVE_AND_ACTIVATE:
			if ('single-use' === response.scope) {
				return messages.updatedExecuted
			} else {
				return wasCreated
					? messages.createdActivated
					: messages.updatedActivated
			}

		case SubmitSnippetAction.SAVE_AND_DEACTIVATE:
			return messages.updatedDeactivated
	}
}

const getActivationErrorNotice = (snippet: Snippet): ScreenNotice => [
	'error',
	__('Snippet could not be activated.', 'code-snippets'),
	<span key="code-snippets-activation-error">
		{__('The snippet was saved, but remains inactive due to this error:', 'code-snippets')}
		{' '}
		<strong>{snippet.code_error?.[0] ?? ''}</strong>
	</span>,
	snippet.code_error_trace ?? undefined
]

export interface UseSubmitSnippet {
	submitSnippet: (snippet: Partial<Snippet> & Pick<Snippet, 'network'>, action?: SubmitSnippetAction) => Promise<Snippet | undefined>
}

export const useSubmitSnippet = (): UseSubmitSnippet => {
	const api = useSnippetsAPI()
	const { acceptSnippet, setIsWorking, setCurrentNotice } = useSnippetForm()

	const submitSnippet: UseSubmitSnippet['submitSnippet'] = useCallback(async (snippet, action) => {
		setCurrentNotice(undefined)
		setIsWorking(true)

		const request = { ...snippet }

		if (SubmitSnippetAction.SAVE_AND_ACTIVATE === action) {
			request.active = true
		} else if (SubmitSnippetAction.SAVE_AND_DEACTIVATE === action) {
			request.active = false
		}

		const result = await (async (): Promise<Snippet | SubmitFailure> => {
			try {
				const { id } = request

				const response = await (undefined === id || 0 === id
					? api.create(request)
					: api.update({ ...request, id }))

				return response.id ? createSnippetObject(response) : { unconfirmed: false }
			} catch (error: unknown) {
				return {
					message: isAxiosError(error) ? describeRequestError(error) : undefined,
					unconfirmed: isUnconfirmedRequest(error)
				}
			} finally {
				setIsWorking(false)
			}
		})()

		const messages = isCondition(snippet) ? conditionMessages : snippetMessages

		if (isSubmitFailure(result)) {
			setCurrentNotice([
				'error',
				describeSubmitFailure({ failure: result, messages, isUpdate: Boolean(request.id) })
			])
			return undefined
		}

		acceptSnippet(result)

		if (result.code_error && SubmitSnippetAction.SAVE_AND_ACTIVATE === action) {
			setCurrentNotice(getActivationErrorNotice(result))
		} else {
			setCurrentNotice(['updated', getSuccessNotice(snippet, result, action)])
		}

		if (request.id && result.id) {
			window.document.title = window.document.title.replace(snippetMessages.addNew, messages.edit)
			window.history.replaceState({}, '', buildUrl(window.CODE_SNIPPETS?.urls.edit, { id: result.id }))
		}

		return result
	}, [acceptSnippet, api, setIsWorking, setCurrentNotice])

	return { submitSnippet }
}
