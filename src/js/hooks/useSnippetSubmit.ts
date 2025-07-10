import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { isAxiosError } from 'axios'
import { useCallback } from 'react'
import { useSnippetsAPI } from './useSnippets'
import type { Dispatch, SetStateAction } from 'react'
import type { ScreenNotice } from '../types/ScreenNotice'
import type { Snippet } from '../types/Snippet'

const getSuccessNotice = (request: Snippet, response: Snippet, active: boolean | undefined) => {
	if (active === undefined) {
		return 0 === request.id
			? __('Snippet created.', 'code-snippets')
			: __('Snippet updated.', 'code-snippets')
	}

	if (0 === request.id && active) {
		return __('Snippet created and activated.', 'code-snippets')
	}

	if (active) {
		return 'single-use' === response.scope
			? __('Snippet updated and executed.', 'code-snippets')
			: __('Snippet updated and activated.', 'code-snippets')
	} else {
		return __('Snippet updated and deactivated')
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
				const { data } = await (0 === snippet.id ? api.create(requestData) : api.update(requestData))
				setIsWorking(false)
				return data.id ? data : undefined
			} catch (error) {
				setIsWorking(false)
				return isAxiosError(error) ? error.message : undefined
			}
		})()

		if (undefined === result || 'string' === typeof result) {
			const message = [
				snippet.id
					? __('Could not create snippet.', 'code-snippets')
					: __('Could not update snippet.', 'code-snippets'),
				result ?? __('The server did not send a valid response.', 'code-snippets')
			]

			setCurrentNotice(['error', message.filter(Boolean).join(' ')])
			return undefined
		} else {
			setSnippet({ ...result })
			setCurrentNotice(['updated', getSuccessNotice(snippet, result, active)])

			if (snippet.id && result.id) {
				window.document.title = window.document.title.replace(
					__('Add New Snippet', 'code-snippets'),
					__('Edit Snippet', 'code-snippets')
				)

				window.history.replaceState({}, '', addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id: result.id }))
			}

			return result
		}
	}, [api, setCurrentNotice, setIsWorking, setSnippet])
}
