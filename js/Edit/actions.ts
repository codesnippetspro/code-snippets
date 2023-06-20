import { __ } from '@wordpress/i18n'
import { AxiosError, AxiosResponse } from 'axios'
import { Dispatch, SetStateAction, useCallback, useMemo } from 'react'
import { ExportSnippets } from '../types/ExportSnippets'
import { Notices } from '../types/Notice'
import { Snippet } from '../types/Snippet'
import { useSnippetsAPI } from '../utils/api/snippets'
import { downloadSnippetExportFile } from '../utils/general'

export interface SnippetActionsProps {
	setSnippet: Dispatch<SetStateAction<Snippet>>
	setIsWorking: Dispatch<SetStateAction<boolean>>
	setNotices: Dispatch<SetStateAction<Notices>>
}

export interface SnippetActionsValue {
	submit: (snippet: Snippet) => void
	submitAndActivate: (snippet: Snippet, activate: boolean) => void
	delete: (snippet: Snippet) => void
	export: (snippet: Snippet) => void
	exportCode: (snippet: Snippet) => void
}

// eslint-disable-next-line max-lines-per-function
export const useSnippetActions = ({ setSnippet, setNotices, setIsWorking }: SnippetActionsProps): SnippetActionsValue => {
	const api = useSnippetsAPI()

	const displayRequestErrors = useCallback((error: AxiosError, message?: string) => {
		console.error('Request failed', error)
		setIsWorking(false)
		setNotices(notices => [
			...notices,
			['error', message ? `${message} ${error.message}` : error.message]
		])
	}, [setIsWorking, setNotices])

	const doSnippetRequest = useCallback((
		createRequest: () => Promise<AxiosResponse<Snippet>>,
		getNotice: (result: Snippet) => string,
		// translators: %s: error message.
		errorNotice: string = __('Something went wrong.', 'code-snippets')
	) => {
		setIsWorking(true)

		createRequest()
			.then(({ data }) => {
				setIsWorking(false)

				if (data.id) {
					setSnippet({ ...data })
					setNotices(notices => [...notices, ['updated', getNotice(data)]])
				} else {
					const errorMessage = `${errorNotice} ${__('The server did not send a valid response.', 'code-snippets')}`
					setNotices(notices => [...notices, ['error', errorMessage]])
				}
			})
			.catch(error => displayRequestErrors(error, errorNotice))
	}, [displayRequestErrors, setIsWorking, setNotices, setSnippet])

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

	const submitSnippet = useCallback((
		snippet: Snippet,
		getCreateNotice: (result: Snippet) => string,
		getUpdateNotice: (result: Snippet) => string
	) => {
		if (snippet.id) {
			doSnippetRequest(
				() => api.update(snippet),
				getUpdateNotice,
				__('Could not update snippet.', 'code-snippets')
			)
		} else {
			doSnippetRequest(
				() => api.create(snippet),
				getCreateNotice,
				__('Could not create snippet.', 'code-snippets')
			)
		}
	}, [api, doSnippetRequest])

	return useMemo(() => ({
		submit: (snippet: Snippet) => {
			submitSnippet(
				snippet,
				() => __('Snippet created.', 'code-snippets'),
				() => __('Snippet updated.', 'code-snippets')
			)
		},

		submitAndActivate: (snippet: Snippet, activate: boolean) => {
			submitSnippet(
				{ ...snippet, active: activate },
				result => result.active ?
					__('Snippet created and activated.', 'code-snippets') :
					__('Snippet created.', 'code-snippets'),
				result => result.active ?
					'single-use' === result.scope ?
						__('Snippet updated and executed.', 'code-snippets') :
						__('Snippet updated and activated.', 'code-snippets') :
					__('Snippet updated.', 'code-snippets')
			)
		},

		delete: (snippet: Snippet) => {
			api.delete(snippet)
				.then(() => setNotices(notices => [
					...notices, ['updated', __('Snippet deleted.', 'code-snippets')]
				]))
				.catch(error => displayRequestErrors(error, __('Could not delete snippet.', 'code-snippets')))
		},

		export: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.export(snippet)),

		exportCode: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.exportCode(snippet))

	}), [api, displayRequestErrors, doFileRequest, setNotices, submitSnippet])
}
