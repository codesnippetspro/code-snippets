import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import { useCallback } from 'react'
import { useSnippetForm } from '../components/EditMenu/SnippetForm/WithSnippetFormContext'
import { createSnippetObject, isCondition } from '../utils/snippets/snippets'
import { buildUrl } from '../utils/urls'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { Snippet } from '../types/Snippet'

const snippetMessages = <const> {
	addNew: __('Create New Snippet', 'code-snippets'),
	edit: __('Edit Snippet', 'code-snippets'),
	created: __('Snippet <strong>created</strong>.', 'code-snippets'),
	updated: __('Snippet <strong>updated</strong>.', 'code-snippets'),
	createdActivated: __('Snippet <strong>created</strong> and <strong>activated</strong>.', 'code-snippets'),
	updatedActivated: __('Snippet <strong>updated</strong> and <strong>activated</strong>.', 'code-snippets'),
	updatedDeactivated: __('Snippet <strong>updated</strong> and <strong>deactivated</strong>'),
	updatedExecuted: __('Snippet <strong>updated</strong> and <strong>executed</strong>.', 'code-snippets'),
	failedCreate: __('Could not create snippet.', 'code-snippets'),
	failedUpdate: __('Could not update snippet.', 'code-snippets')
}

const conditionCreated = __('Condition <strong>created</strong>.', 'code-snippets')
const conditionUpdated = __('Condition <strong>updated</strong>.', 'code-snippets')

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

const getSuccessNotice = (request: Snippet, response: Snippet, action: SubmitSnippetAction): string => {
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

const SUBMIT_ACTION_DELTA: Record<SubmitSnippetAction, Partial<Snippet>> = {
	[SubmitSnippetAction.SAVE]: {},
	[SubmitSnippetAction.SAVE_AND_ACTIVATE]: { active: true },
	[SubmitSnippetAction.SAVE_AND_DEACTIVATE]: { active: false },
	[SubmitSnippetAction.SAVE_AND_EXECUTE]: { active: true }
}

export interface UseSubmitSnippet {
	submitSnippet: (action?: SubmitSnippetAction, snippetOverride?: Snippet) => Promise<Snippet | undefined>
}

export const useSubmitSnippet = (): UseSubmitSnippet => {
	const api = useSnippetsAPI()
	const { setIsWorking, setCurrentNotice, snippet, setSnippet } = useSnippetForm()

	const submitSnippet = useCallback(async (
		action: SubmitSnippetAction = SubmitSnippetAction.SAVE,
		snippetOverride?: Snippet
	) => {
		setCurrentNotice(undefined)
		setIsWorking(true)

		// Use the override if provided (prevents stale state issues), otherwise use current context state
		const activeSnippet = snippetOverride ?? snippet

		const result = await (async (): Promise<Snippet | string | undefined> => {
			try {
				const request: Snippet = { ...activeSnippet, ...SUBMIT_ACTION_DELTA[action] }
				const response = await (0 === request.id ? api.create(request) : api.update(request))
				return response.id ? response : undefined
			} catch (error) {
				return isAxiosError(error) ? error.message : undefined
			} finally {
				setIsWorking(false)
			}
		})()

		const messages = isCondition(activeSnippet) ? conditionMessages : snippetMessages

		if (undefined === result || 'string' === typeof result) {
			const message = [
				activeSnippet.id ? messages.failedUpdate : messages.failedCreate,
				result ?? __('The server did not send a valid response.', 'code-snippets')
			]

			setCurrentNotice(['error', message.filter(Boolean).join(' ')])
			return undefined
		} else {
			const updatedSnippet = createSnippetObject(result)
			setSnippet(updatedSnippet)
			setCurrentNotice(['updated', getSuccessNotice(activeSnippet, updatedSnippet, action)])

			if (activeSnippet.id && updatedSnippet.id) {
				window.document.title = window.document.title.replace(snippetMessages.addNew, messages.edit)
				window.history.replaceState({}, '', buildUrl(window.CODE_SNIPPETS?.urls.edit, { id: updatedSnippet.id }))
			}

			return updatedSnippet
		}
	}, [api, setIsWorking, setCurrentNotice, snippet, setSnippet])

	return { submitSnippet }
}
