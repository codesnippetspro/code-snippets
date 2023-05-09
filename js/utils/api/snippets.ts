import { useEffect, useMemo, useState } from 'react'
import axios, { AxiosResponse } from 'axios'
import { addQueryArgs } from '@wordpress/url'
import { ExportSnippets } from '../../types/ExportSnippets'
import { Snippet } from '../../types/Snippet'
import { isNetworkAdmin } from '../general'

const ROUTE_BASE = window.CODE_SNIPPETS?.restAPI.snippets

export interface Snippets {
	fetchAll: (network?: boolean | null) => Promise<AxiosResponse<Snippet[]>>
	fetch: (snippetId: number, network?: boolean | null) => Promise<AxiosResponse<Snippet>>
	create: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	update: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	delete: (snippet: Snippet) => Promise<AxiosResponse<void>>
	activate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	deactivate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	export: (snippet: Snippet) => Promise<AxiosResponse<ExportSnippets>>
	exportCode: (snippet: Snippet) => Promise<AxiosResponse<string>>
}

export const useSnippetsAPI = (): Snippets => {
	const axiosInstance = useMemo(() =>
		axios.create({
			headers: { 'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce }
		}), [])

	const buildURL = ({ id, network }: Snippet, action?: string) =>
		addQueryArgs([ROUTE_BASE, id, action].filter(Boolean).join('/'), network ? { network } : undefined)

	return useMemo((): Snippets => ({
		fetchAll: network =>
			axiosInstance.get<Snippet[]>(addQueryArgs(ROUTE_BASE, { network })),

		fetch: (snippetId, network) =>
			axiosInstance.get<Snippet>(addQueryArgs(`${ROUTE_BASE}/${snippetId}`, { network })),

		create: snippet => {
			console.info(`Sending request to ${ROUTE_BASE}`, snippet)
			return axiosInstance.post<Snippet>(`${ROUTE_BASE}`, snippet)
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
			axiosInstance.get<ExportSnippets>(buildURL(snippet, 'export')),

		exportCode: snippet =>
			axiosInstance.get<string, AxiosResponse<string>>(buildURL(snippet, 'export-code'))
	}), [axiosInstance])
}

export const useSnippets = (): Snippet[] | undefined => {
	const api = useSnippetsAPI()
	const [snippets, setSnippets] = useState<Snippet[]>()

	useEffect(() => {
		if (!snippets) {
			api.fetchAll(isNetworkAdmin())
				.then(response => setSnippets(response.data))
		}
	}, [api, snippets])

	return snippets
}
