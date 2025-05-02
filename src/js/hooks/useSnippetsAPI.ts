import { useMemo } from 'react'
import { addQueryArgs } from '@wordpress/url'
import { REST_API_AXIOS_CONFIG, REST_SNIPPETS_BASE } from '../utils/restAPI'
import { createSnippetObject } from '../utils/snippets/snippets'
import { useAxios } from './useAxios'
import type { SnippetSchema } from '../types/schema/SnippetSchema'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/schema/SnippetsExport'

export interface SnippetsAPI {
	fetchAll: (network?: boolean | null) => Promise<Snippet[]>
	fetch: (snippetId: number, network?: boolean | null) => Promise<Snippet>
	create: (snippet: Snippet) => Promise<Snippet>
	update: (snippet: Snippet) => Promise<Snippet>
	delete: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<void>
	activate: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<Snippet>
	deactivate: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<Snippet>
	export: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<SnippetsExport>
	exportCode: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<string>
	attach: (snippet: Pick<Snippet, 'id' | 'network' | 'conditionId'>) => Promise<void>
	detach: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<void>
}

const buildURL = ({ id, network }: Pick<Snippet, 'id' | 'network'>, action?: string) =>
	addQueryArgs(
		[REST_SNIPPETS_BASE, id, action].filter(Boolean).join('/'),
		{ network: network ? true : undefined }
	)

const mapToSchema = ({ conditionId, ...fields }: Snippet): SnippetSchema => ({
	condition_id: conditionId,
	...fields
})

export const useSnippetsAPI = (): SnippetsAPI => {
	const { get, post, put, del } = useAxios(REST_API_AXIOS_CONFIG)

	return useMemo((): SnippetsAPI => ({
		fetchAll: network =>
			get<SnippetSchema[]>(addQueryArgs(REST_SNIPPETS_BASE, { network }))
				.then(response => response.map(createSnippetObject)),

		fetch: (snippetId, network) =>
			get<SnippetSchema>(addQueryArgs(`${REST_SNIPPETS_BASE}/${snippetId}`, { network }))
				.then(createSnippetObject),

		create: snippet =>
			post<SnippetSchema>(REST_SNIPPETS_BASE, mapToSchema(snippet))
				.then(createSnippetObject),

		update: snippet =>
			post<SnippetSchema>(buildURL(snippet), mapToSchema(snippet))
				.then(createSnippetObject),

		delete: snippet =>
			del(buildURL(snippet)),

		activate: snippet =>
			post<SnippetSchema>(buildURL(snippet, 'activate'))
				.then(createSnippetObject),

		deactivate: snippet =>
			post<SnippetSchema>(buildURL(snippet, 'deactivate'))
				.then(createSnippetObject),

		export: snippet =>
			get<SnippetsExport>(buildURL(snippet, 'export')),

		exportCode: snippet =>
			get<string>(buildURL(snippet, 'export-code')),

		attach: snippet =>
			put(buildURL(snippet, 'attach'), { condition_id: snippet.conditionId }),

		detach: snippet =>
			put(buildURL(snippet, 'detach'))

	}), [get, post, put, del])
}
