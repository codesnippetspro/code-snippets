import { useEffect, useMemo, useState } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { handleUnknownError } from '../utils/errors'
import { REST_API_AXIOS_CONFIG, REST_SNIPPETS_BASE } from '../utils/restAPI'
import { isNetworkAdmin } from '../utils/screen'
import { createSnippetObject } from '../utils/snippets'
import { useAxios } from './useAxios'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/SnippetsExport'
import type { AxiosResponse } from 'axios'

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
		[REST_SNIPPETS_BASE, id, action].filter(Boolean).join('/'),
		{ network: network ? true : undefined }
	)

export const useSnippetsAPI = (): SnippetsAPI => {
	const { get, post, del } = useAxios(REST_API_AXIOS_CONFIG)

	return useMemo((): SnippetsAPI => ({
		fetchAll: network =>
			get<Snippet[]>(addQueryArgs(REST_SNIPPETS_BASE, { network })),

		fetch: (snippetId, network) =>
			get<Snippet>(addQueryArgs(`${REST_SNIPPETS_BASE}/${snippetId}`, { network })),

		create: snippet =>
			post<Snippet, Snippet>(REST_SNIPPETS_BASE, snippet),

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
				.then(response =>
					setSnippets(response.data.map(snippet => createSnippetObject(snippet))))
				.catch(handleUnknownError)
		}
	}, [api, snippets])

	return snippets
}
