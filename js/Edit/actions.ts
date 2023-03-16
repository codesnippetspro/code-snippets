import { __, sprintf } from '@wordpress/i18n'
import { AxiosError, AxiosResponse } from 'axios'
import { Dispatch, SetStateAction, useCallback, useMemo } from 'react'
import { Notices } from '../types/Notice'
import { Snippet, SnippetType } from '../types/Snippet'
import { useSnippetsAPI } from '../utils/api'
import { downloadAsFile } from '../utils/general'
import { getSnippetType } from '../utils/snippets'

const MIME_TYPES: Record<SnippetType, string> = {
	php: 'text/php',
	html: 'text/php',
	css: 'text/css',
	js: 'text/javascript',
	cond: 'application/json'
}

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
		console.error('request failed', error)
		setIsWorking(false)

		const errorMessage = message ?
			sprintf(message, error.message) :
			error.message

		setNotices(notices => [...notices, ['error', errorMessage]])
	}, [setIsWorking, setNotices])

	const doSnippetRequest = useCallback((
		createRequest: () => Promise<AxiosResponse<Snippet>>,
		getNotice: (result: Snippet) => string,
		errorNotice?: string
	) => {
		setIsWorking(true)

		createRequest()
			.then(({ data }) => {
				setSnippet({ ...data })
				setIsWorking(false)
				setNotices(notices => [...notices, ['updated', getNotice(data)]])
			})
			.catch(error => displayRequestErrors(error, errorNotice))
	}, [displayRequestErrors, setIsWorking, setNotices, setSnippet])

	const doFileRequest = useCallback((snippet: Snippet, createRequest: () => Promise<AxiosResponse<string>>) => {
		setIsWorking(true)

		createRequest()
			.then(response => {
				setIsWorking(false)
				console.info('file response', response)
				const filename = snippet.name.toLowerCase().replace(/[^\w-]+/, '-')
				downloadAsFile(response.data, filename, MIME_TYPES[getSnippetType(snippet)])
			})
			// translators: %s: error message.
			.catch(error => displayRequestErrors(error, __('Could not download export file: %s.', 'code-snippets')))
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
				// translators: %s: error message.
				__('Could not create snippet: %s.', 'code-snippets')
			)
		} else {
			doSnippetRequest(
				() => api.create(snippet),
				getCreateNotice,
				// translators: %s: error message.
				__('Could not update snippet: %s.', 'code-snippets')
			)
		}
	}, [api, doSnippetRequest])

	return useMemo(() => ({
		submit: (snippet: Snippet) => {
			submitSnippet(
				snippet,
				() => __('Snippet created successfully.', 'code-snippets'),
				() => __('Snippet updated successfully.', 'code-snippets')
			)
		},

		submitAndActivate: (snippet: Snippet, activate: boolean) => {
			submitSnippet(
				{ ...snippet, active: activate },
				result => result.active ?
					__('Snippet created and activated successfully.', 'code-snippets') :
					__('Snippet created successfully.', 'code-snippets'),
				result => result.active ?
					'single-use' === result.scope ?
						__('Snippet updated and executed successfully.', 'code-snippets') :
						__('Snippet updated and activated successfully.', 'code-snippets') :
					__('Snippet updated and deactivated successfully.', 'code-snippets')
			)
		},

		delete: (snippet: Snippet) => {
			if (confirm([
				__('You are about to permanently delete this snippet.', 'code-snippets'),
				__("'Cancel' to stop, 'OK' to delete.", 'code-snippets')
			].join('\n'))) {
				api.delete(snippet)
					.then(() => setNotices(notices => [
						...notices, ['updated', __('Snippet deleted.', 'code-snippets')]
					]))
					// translators: %s: error message.
					.catch(error => displayRequestErrors(error, __('Could not delete snippet: %s.', 'code-snippets')))
			}
		},

		export: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.export(snippet)),

		exportCode: (snippet: Snippet) =>
			doFileRequest(snippet, () => api.exportCode(snippet))

	}), [api, displayRequestErrors, doFileRequest, setNotices, submitSnippet])
}
