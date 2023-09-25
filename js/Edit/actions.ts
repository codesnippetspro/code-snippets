import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { AxiosError, AxiosResponse, isAxiosError } from 'axios'
import { Dispatch, SetStateAction, useCallback, useMemo } from 'react'
import { ExportSnippets } from '../types/ExportSnippets'
import { Snippet } from '../types/Snippet'
import { useSnippetsAPI } from '../utils/api/snippets'
import { downloadSnippetExportFile } from '../utils/general'
import { Notice } from '../types/Notice'

export interface SnippetActionsProps {
	setSnippet: Dispatch<SetStateAction<Snippet>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	setCurrentNotice: Dispatch<SetStateAction<Notice | undefined>>
}

export interface SnippetActionsValue {
	submit: (snippet: Snippet) => void
	submitAndActivate: (snippet: Snippet, active: boolean) => void
	delete: (snippet: Snippet) => void
	export: (snippet: Snippet) => void
	exportCode: (snippet: Snippet) => void
}

// eslint-disable-next-line max-lines-per-function
export const useSnippetActions = ({
	setSnippet,
	setIsWorking,
	setCurrentNotice
}: SnippetActionsProps): SnippetActionsValue => {
	const api = useSnippetsAPI()

	const displayRequestErrors = useCallback((error: AxiosError | unknown, message?: string) => {
		console.error('Request failed', error)
		setIsWorking(false)
		setCurrentNotice(['error', [message, isAxiosError(error) ? error.message : ''].filter(Boolean).join(' ')])
	}, [setIsWorking, setCurrentNotice])

	const doSnippetRequest = useCallback(async (
		createRequest: () => Promise<AxiosResponse<Snippet>>,
		getNotice: (result: Snippet) => string,
		// translators: %s: error message.
		errorNotice: string = __('Something went wrong.', 'code-snippets')
	): Promise<Snippet | undefined> => {
		setIsWorking(true)
		setCurrentNotice(undefined)

		try {
			const { data } = await createRequest()
			setIsWorking(false)

			if (data.id) {
				setSnippet({ ...data })
				setCurrentNotice(['updated', getNotice(data)])
				return data
			}

			console.info('Response data', data)
			setCurrentNotice(['error', `${errorNotice} ${__('The server did not send a valid response.', 'code-snippets')}`])
			return undefined
		} catch (error) {
			displayRequestErrors(error, errorNotice)
			return undefined
		}
	}, [displayRequestErrors, setIsWorking, setSnippet, setCurrentNotice])

	const doFileRequest = useCallback((snippet: Snippet, createRequest: () => Promise<AxiosResponse<string | ExportSnippets>>) => {
		setIsWorking(true)

		createRequest()
			.then(response => {
				const data = response.data
				setIsWorking(false)
				console.info('file response', response)

				if ('string' === typeof data) {
					downloadSnippetExportFile(data, snippet)
				} else {
					const JSON_INDENT_SPACES = 2
					downloadSnippetExportFile(JSON.stringify(data, undefined, JSON_INDENT_SPACES), snippet, 'json')
				}
			})
			// translators: %s: error message.
			.catch(error => displayRequestErrors(error, __('Could not download export file.', 'code-snippets')))
	}, [displayRequestErrors, setIsWorking])

	const submitSnippet = useCallback(async (
		snippet: Snippet,
		getCreateNotice: (result: Snippet) => string,
		getUpdateNotice: (result: Snippet) => string
	) => {
		if (snippet.id) {
			return await doSnippetRequest(
				() => api.update(snippet),
				getUpdateNotice,
				__('Could not update snippet.', 'code-snippets')
			)
		} else {
			const result = await doSnippetRequest(
				() => api.create(snippet),
				getCreateNotice,
				__('Could not create snippet.', 'code-snippets')
			)

			if (result?.id) {
				window.document.title = window.document.title.replace(
					__('Add New Snippet', 'code-snippets'),
					__('Edit Snippet', 'code-snippets')
				)

				window.history.replaceState({}, window.document.title, addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id: result.id }))
			}

			return result
		}
	}, [api, doSnippetRequest])

	return useMemo(() => ({
		submit: async (snippet: Snippet) =>
			await submitSnippet(
				snippet,
				() => __('Snippet created.', 'code-snippets'),
				() => __('Snippet updated.', 'code-snippets')
			),

		submitAndActivate: async (snippet: Snippet, active: boolean) =>
			await submitSnippet(
				{ ...snippet, active },
				result => result.active ?
					__('Snippet created and activated.', 'code-snippets') :
					__('Snippet created.', 'code-snippets'),
				result => result.active ?
					'single-use' === result.scope ?
						__('Snippet updated and executed.', 'code-snippets') :
						__('Snippet updated and activated.', 'code-snippets') :
					__('Snippet updated.', 'code-snippets')
			),

		delete: (snippet: Snippet) => {
			api.delete(snippet)
				.then(() => {
					window.location.replace(addQueryArgs(window?.CODE_SNIPPETS?.urls.manage, { result: 'deleted' }))
				})
				.catch(error => displayRequestErrors(error, __('Could not delete snippet.', 'code-snippets')))
		},

		export: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.export(snippet)),

		exportCode: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.exportCode(snippet))

	}), [api, displayRequestErrors, doFileRequest, submitSnippet])
}
