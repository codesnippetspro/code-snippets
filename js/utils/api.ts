import { Dispatch, SetStateAction, useCallback, useMemo } from 'react'
import axios, { AxiosResponse } from 'axios'
import { addQueryArgs } from '@wordpress/url'
import { Snippet, SnippetType } from '../types/Snippet'
import { downloadAsFile } from './general'
import { getSnippetType } from './snippets'

const CONFIG = window.CODE_SNIPPETS_EDIT?.restAPI

const MIME_TYPES: Record<SnippetType, string> = {
	php: 'text/php',
	html: 'text/php',
	css: 'text/css',
	js: 'text/javascript',
	cond: 'application/json'
}

export interface SnippetsAPI {
	fetch: (snippetId: number, network?: boolean | null) => Promise<Snippet>
	create: (snippet: Snippet) => Promise<Snippet>
	update: (snippet: Snippet) => Promise<Snippet>
	delete: (snippet: Snippet) => Promise<void>
	activate: (snippet: Snippet) => Promise<Snippet>
	deactivate: (snippet: Snippet) => Promise<Snippet>
	export: (snippet: Snippet) => Promise<string>
	exportCode: (snippet: Snippet) => Promise<string>
}

export const useSnippetsAPI = (setSnippet: Dispatch<SetStateAction<Snippet>>): SnippetsAPI => {
	const axiosInstance = useMemo(() =>
		axios.create({
			headers: { 'X-WP-Nonce': CONFIG?.nonce }
		}), [])

	const unpackResponse = useCallback((response: AxiosResponse<Snippet, unknown>): Snippet => {
		console.info('received response', response)
		setSnippet(response.data)
		return response.data
	}, [setSnippet])

	const downloadResponse = useCallback((response: AxiosResponse<string, unknown>, snippet: Snippet): string => {
		console.info('received response', response)
		const filename = snippet.name.toLowerCase().replace(/[^\w-]+/, '-')
		downloadAsFile(response.data, filename, MIME_TYPES[getSnippetType(snippet)])
		return response.data
	}, [])

	const buildURL = ({ id, network }: Snippet, action?: string) =>
		addQueryArgs([CONFIG?.base, id, action].filter(Boolean).join('/'), { network })

	return useMemo((): SnippetsAPI => ({
		fetch: (snippetId, network) =>
			axiosInstance.get(addQueryArgs(`${CONFIG?.base}/${snippetId}`, { network }))
				.then(unpackResponse),

		create: snippet =>
			axiosInstance.post<Snippet>(`${CONFIG?.base}`, snippet)
				.then(unpackResponse),

		update: snippet =>
			axiosInstance.post<Snippet, AxiosResponse<Snippet>, Snippet>(buildURL(snippet), snippet)
				.then(unpackResponse),

		delete: (snippet: Snippet) =>
			axiosInstance.delete(buildURL(snippet)),

		activate: snippet =>
			axiosInstance.post<Snippet, AxiosResponse<Snippet>, never>(buildURL(snippet, 'activate'))
				.then(unpackResponse),

		deactivate: snippet =>
			axiosInstance.post<Snippet, AxiosResponse<Snippet>, never>(buildURL(snippet, 'deactivate'))
				.then(unpackResponse),

		export: snippet =>
			axiosInstance.get<string>(buildURL(snippet, 'export'))
				.then(response => downloadResponse(response, snippet)),

		exportCode: snippet =>
			axiosInstance.get<string, AxiosResponse<string>>(buildURL(snippet, 'export-code'))
				.then(response => downloadResponse(response, snippet))
	}), [axiosInstance, downloadResponse, unpackResponse])
}
