import { useEffect, useMemo, useState } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { handleUnknownError } from '../utils/errors'
import { REST_API_AXIOS_CONFIG, REST_SNIPPETS_BASE } from '../utils/restAPI'
import { isNetworkAdmin } from '../utils/screen'
import { createSnippetObject } from '../utils/snippets'
import { useAxios } from './useAxios'
import type { SnippetSchema } from '../types/api/SnippetSchema'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/api/SnippetsExport'

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

const mapToSchema = ({ conditionId, ...fields }: Snippet): SnippetSchema => ({
	condition_id: conditionId,
	...fields
})

const mapFromSchema = (schema: SnippetSchema): Snippet =>
	createSnippetObject(schema)

export const useSnippetsAPI = (): SnippetsAPI => {
	const { get, post, del } = useAxios(REST_API_AXIOS_CONFIG)

	return useMemo((): SnippetsAPI => ({
		fetchAll: network =>
			get<SnippetSchema[]>(addQueryArgs(REST_SNIPPETS_BASE, { network }))
				.then(response => response.map(mapFromSchema)),

		fetch: (snippetId, network) =>
			get<SnippetSchema>(addQueryArgs(`${REST_SNIPPETS_BASE}/${snippetId}`, { network }))
				.then(response => mapFromSchema(response)),

		create: snippet =>
			post<SnippetSchema>(REST_SNIPPETS_BASE, mapToSchema(snippet))
				.then(response => mapFromSchema(response)),

		update: snippet =>
			post<SnippetSchema>(buildURL(snippet), mapToSchema(snippet))
				.then(response => mapFromSchema(response)),

		delete: (snippet: Snippet) =>
			del(buildURL(snippet)),

		activate: snippet =>
			post<SnippetSchema>(buildURL(snippet, 'activate'))
				.then(response => mapFromSchema(response)),

		deactivate: snippet =>
			post<SnippetSchema>(buildURL(snippet, 'deactivate'))
				.then(response => mapFromSchema(response)),

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
