import { useEffect, useMemo, useState } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { handleUnknownError } from '../utils/errors'
import { REST_API_AXIOS_CONFIG, REST_SNIPPETS_BASE } from '../utils/restAPI'
import { isNetworkAdmin } from '../utils/screen'
import { createSnippetObject } from '../utils/snippets/snippets'
import { useAxios } from './useAxios'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/SnippetsExport'

export interface SnippetsAPI {
	fetchAll: (network?: boolean | null) => Promise<Snippet[]>
	fetch: (snippetId: number, network?: boolean | null) => Promise<Snippet>
	create: (snippet: Snippet) => Promise<Snippet>
	update: (snippet: Snippet) => Promise<Snippet>
	delete: (snippet: Snippet) => Promise<void>
	activate: (snippet: Snippet) => Promise<Snippet>
	deactivate: (snippet: Snippet) => Promise<Snippet>
	export: (snippet: Snippet) => Promise<SnippetsExport>
	exportCode: (snippet: Snippet) => Promise<string>
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
			post<Snippet>(REST_SNIPPETS_BASE, snippet),

		update: snippet =>
			post<Snippet>(buildURL(snippet), snippet),

		delete: (snippet: Snippet) =>
			del(buildURL(snippet)),

		activate: snippet =>
			post<Snippet>(buildURL(snippet, 'activate')),

		deactivate: snippet =>
			post<Snippet>(buildURL(snippet, 'deactivate')),

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
					setSnippets(response.map(snippet => createSnippetObject(snippet))))
				.catch(handleUnknownError)
		}
	}, [api, snippets])

	return snippets
}
