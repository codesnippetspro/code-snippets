import { useMemo } from 'react'
import axios, { AxiosResponse } from 'axios'
import { addQueryArgs } from '@wordpress/url'
import { Snippet } from '../types/Snippet'

const CONFIG = window.CODE_SNIPPETS_EDIT?.restAPI

export interface SnippetsAPI {
	fetch: (snippetId: number, network?: boolean | null) => Promise<AxiosResponse<Snippet>>
	create: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	update: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	delete: (snippet: Snippet) => Promise<AxiosResponse<void>>
	activate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	deactivate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	export: (snippet: Snippet) => Promise<AxiosResponse<string>>
	exportCode: (snippet: Snippet) => Promise<AxiosResponse<string>>
}

export const useSnippetsAPI = (): SnippetsAPI => {
	const axiosInstance = useMemo(() =>
		axios.create({
			headers: { 'X-WP-Nonce': CONFIG?.nonce }
		}), [])

	const buildURL = ({ id, network }: Snippet, action?: string) =>
		addQueryArgs([CONFIG?.base, id, action].filter(Boolean).join('/'), { network })

	return useMemo((): SnippetsAPI => ({
		fetch: (snippetId, network) =>
			axiosInstance.get(addQueryArgs(`${CONFIG?.base}/${snippetId}`, { network })),

		create: snippet => {
			console.info(`Sending request to ${CONFIG?.base}`, snippet)
			return axiosInstance.post<Snippet>(`${CONFIG?.base}`, snippet)
				.then(response => {
					console.info('Received response', response)
					return response
				})
		},

		update: snippet => {
			const url = buildURL(snippet)
			console.info(`Sending request to ${url}`, snippet)
			return axiosInstance.post<Snippet, AxiosResponse<Snippet>, Snippet>(url, snippet)
				.then(response => {
					console.info('Received response', response)
					return response
				})
		},

		delete: (snippet: Snippet) =>
			axiosInstance.delete(buildURL(snippet)),

		activate: snippet =>
			axiosInstance.post<Snippet, AxiosResponse<Snippet>, never>(buildURL(snippet, 'activate')),

		deactivate: snippet =>
			axiosInstance.post<Snippet, AxiosResponse<Snippet>, never>(buildURL(snippet, 'deactivate')),

		export: snippet =>
			axiosInstance.get<string>(buildURL(snippet, 'export')),

		exportCode: snippet =>
			axiosInstance.get<string, AxiosResponse<string>>(buildURL(snippet, 'export-code'))
	}), [axiosInstance])
}
