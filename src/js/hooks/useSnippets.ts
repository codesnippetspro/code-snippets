import { useEffect, useMemo, useState } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { handleUnknownError } from '../utils/errors'
import { isNetworkAdmin } from '../utils/general'
import { useAxios } from './useAxios'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/SnippetsExport'
import type { AxiosResponse, CreateAxiosDefaults } from 'axios'

const ROUTE_BASE = window.CODE_SNIPPETS?.restAPI.snippets

const AXIOS_CONFIG: CreateAxiosDefaults = {
	headers: { 'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce }
}

export interface SnippetsAPI {
	fetchAll: (network?: boolean | null) => Promise<AxiosResponse<Snippet[]>>
	fetch: (snippetId: number, network?: boolean | null) => Promise<AxiosResponse<Snippet>>
	create: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	update: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	delete: (snippet: Snippet) => Promise<AxiosResponse<void>>
	activate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	deactivate: (snippet: Snippet) => Promise<AxiosResponse<Snippet>>
	export: (snippet: Snippet) => Promise<AxiosResponse<SnippetsExport>>
	exportCode: (snippet: Snippet) => Promise<AxiosResponse<string>>
}

const buildURL = ({ id, network }: Snippet, action?: string) =>
	addQueryArgs(
		[ROUTE_BASE, id, action].filter(Boolean).join('/'),
		{ network: network ? true : undefined }
	)

export const useSnippetsAPI = (): SnippetsAPI => {
	const { get, post, del } = useAxios(AXIOS_CONFIG)

	return useMemo((): SnippetsAPI => ({
		fetchAll: network =>
			get<Snippet[]>(addQueryArgs(ROUTE_BASE, { network })),

		fetch: (snippetId, network) =>
			get<Snippet>(addQueryArgs(`${ROUTE_BASE}/${snippetId}`, { network })),

		create: snippet =>
			post<Snippet, Snippet>(`${ROUTE_BASE}`, snippet),

		update: snippet =>
			post<Snippet, Snippet>(buildURL(snippet), snippet),

		delete: (snippet: Snippet) =>
			del(buildURL(snippet)),

		activate: snippet =>
			post<Snippet, never>(buildURL(snippet, 'activate')),

		deactivate: snippet =>
			post<Snippet, never>(buildURL(snippet, 'deactivate')),

		export: snippet =>
			get<SnippetsExport>(buildURL(snippet, 'export')),

		exportCode: snippet =>
			get<string>(buildURL(snippet, 'export-code'))
	}), [get, post, del])
}

export const useSnippets = (): Snippet[] | undefined => {
	const api = useSnippetsAPI()
	const [snippets, setSnippets] = useState<Snippet[]>()

	useEffect(() => {
		if (!snippets) {
			api.fetchAll(isNetworkAdmin())
				.then(response => setSnippets(response.data))
				.catch(handleUnknownError)
		}
	}, [api, snippets])

	return snippets
}
