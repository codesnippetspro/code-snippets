import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { isAxiosError } from 'axios'
import { useCallback } from 'react'
import { createSnippetObject } from '../utils/snippets'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { Dispatch, SetStateAction } from 'react'
import type { ScreenNotice } from '../types/ScreenNotice'
import type { Snippet } from '../types/Snippet'

const messages = <const> {
	addNew: __('Add New Snippet', 'code-snippets'),
	edit: __('Edit Snippet', 'code-snippets'),
	created: __('Snippet created.', 'code-snippets'),
	updated: __('Snippet updated.', 'code-snippets'),
	createdActivated: __('Snippet created and activated.', 'code-snippets'),
	updatedActivated: __('Snippet updated and activated.', 'code-snippets'),
	updatedExecuted: __('Snippet updated and executed.', 'code-snippets'),
	updatedDeactivated: __('Snippet updated and deactivated'),
	failedCreate: __('Could not create snippet.', 'code-snippets'),
	failedUpdate: __('Could not update snippet.', 'code-snippets')
}

const getSuccessNotice = (request: Snippet, response: Snippet, active: boolean | undefined): string => {
	if (active === undefined) {
		return 0 === request.id ? messages.created : messages.updated
	}

	if (0 === request.id && active) {
		return messages.createdActivated
	}

	if (active) {
		return 'single-use' === response.scope
			? messages.updatedExecuted
			: messages.updatedActivated
	} else {
		return messages.updatedDeactivated
	}
}

export const useSnippetSubmit = (
	setSnippet: Dispatch<SetStateAction<Snippet>>,
	setIsWorking: Dispatch<SetStateAction<boolean>>,
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
): (snippet: Snippet, active?: boolean) => Promise<Snippet | undefined> => {
	const api = useSnippetsAPI()

	return useCallback(async (snippet: Snippet, active?: boolean) => {
		setIsWorking(true)
		setCurrentNotice(undefined)

		const result = await (async (): Promise<Snippet | string | undefined> => {
			try {
				const requestData: Snippet = { ...snippet, active: active ?? snippet.active }
				const data = await (0 === snippet.id ? api.create(requestData) : api.update(requestData))
				setIsWorking(false)
				return data.id ? data : undefined
			} catch (error) {
				setIsWorking(false)
				return isAxiosError(error) ? error.message : undefined
			}
		})()

		if (undefined === result || 'string' === typeof result) {
			const message = [
				snippet.id ? messages.failedCreate : messages.failedUpdate,
				result ?? __('The server did not send a valid response.', 'code-snippets')
			]

			setCurrentNotice(['error', message.filter(Boolean).join(' ')])
			return undefined
		} else {
			setSnippet(createSnippetObject(result))
			setCurrentNotice(['updated', getSuccessNotice(snippet, result, active)])

			if (snippet.id && result.id) {
				window.document.title = window.document.title.replace(messages.addNew, messages.edit)
				window.history.replaceState({}, '', addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id: result.id }))
			}

			return result
		}
	}, [api, setCurrentNotice, setIsWorking, setSnippet])
}
