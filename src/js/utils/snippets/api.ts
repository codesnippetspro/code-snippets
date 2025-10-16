import { addQueryArgs } from '@wordpress/url'
import { REST_SNIPPETS_BASE } from '../restAPI'
import { createSnippetObject } from './snippets'
import type { RestAPI } from '../../hooks/useRestAPI'
import type { SnippetSchema, WritableSnippetSchema } from '../../types/schema/SnippetSchema'
import type { Snippet } from '../../types/Snippet'
import type { SnippetsExport } from '../../types/schema/SnippetsExport'

export interface SnippetsAPI {
	fetchAll: (network?: boolean | null) => Promise<Snippet[]>
	fetch: (snippetId: number, network?: boolean | null) => Promise<Snippet>
	create: (snippet: Snippet) => Promise<Snippet>
	update: (snippet: Pick<Snippet, 'id' | 'network'> & Partial<Snippet>) => Promise<Snippet>
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

const mapToSchema = ({
	name,
	desc,
	code,
	tags,
	scope,
	priority,
	active,
	network,
	conditionId
}: Partial<Snippet>): WritableSnippetSchema => ({
	name,
	desc,
	code,
	tags,
	scope,
	priority,
	active,
	network,
	condition_id: conditionId
})

export const buildSnippetsAPI = ({ get, post, del, put }: RestAPI): SnippetsAPI => ({
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
		post<SnippetSchema>(snippet.id ? buildURL(snippet) : REST_SNIPPETS_BASE, mapToSchema(snippet))
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
})
