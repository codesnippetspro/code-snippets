import React, { useMemo } from 'react'
import { createContextHook } from '../utils/bootstrap'
import { REST_BASES } from '../utils/restAPI'
import { createSnippetObject } from '../utils/snippets/snippets'
import { buildUrl } from '../utils/urls'
import { useRestAPI } from './useRestAPI'
import type { Snippet } from '../types/Snippet'
import type { SnippetsExport } from '../types/schema/SnippetsExport'
import type { SnippetSchema, WritableSnippetSchema } from '../types/schema/SnippetSchema'
import type { RestAPI } from './useRestAPI'
import type { PropsWithChildren } from 'react'

export interface SnippetsAPI {
	fetchAll: (network?: boolean | null) => Promise<Snippet[]>
	fetch: (snippetId: number, network?: boolean | null) => Promise<Snippet>
	create: (snippet: Partial<Snippet>) => Promise<Snippet>
	update: (snippet: Pick<Snippet, 'id' | 'network'> & Partial<Snippet>) => Promise<Snippet>
	delete: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<void>
	restore: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<void>
	activate: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<Snippet>
	deactivate: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<Snippet>
	export: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<SnippetsExport>
	exportCode: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<string>
	attach: (snippet: Pick<Snippet, 'id' | 'network' | 'conditionId'>) => Promise<void>
	detach: (snippet: Pick<Snippet, 'id' | 'network'>) => Promise<void>
}

const buildSnippetUrl = ({ id, network }: Pick<Snippet, 'id' | 'network'>, action?: string) =>
	buildUrl([REST_BASES.snippets, id, action].filter(Boolean).join('/'), { network })

const mapToSchema = ({
	name,
	desc,
	code,
	tags,
	scope,
	priority,
	active,
	network,
	locked,
	trashed,
	shared_network,
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
	locked,
	trashed,
	shared_network,
	condition_id: conditionId
})

const buildSnippetsAPI = ({ get, post, del, put }: RestAPI): SnippetsAPI => ({
	fetchAll: network =>
		get<SnippetSchema[]>(buildUrl(REST_BASES.snippets, { network }))
			.then(response => response.map(createSnippetObject)),

	fetch: (snippetId, network) =>
		get<SnippetSchema>(buildUrl(`${REST_BASES.snippets}/${snippetId}`, { network }))
			.then(createSnippetObject),

	create: snippet =>
		post<SnippetSchema, WritableSnippetSchema>(REST_BASES.snippets, mapToSchema(snippet))
			.then(createSnippetObject),

	update: snippet =>
		post<SnippetSchema, WritableSnippetSchema>(snippet.id ? buildSnippetUrl(snippet) : REST_BASES.snippets, mapToSchema(snippet))
			.then(createSnippetObject),

	delete: snippet =>
		del(buildSnippetUrl(snippet)),

	restore: snippet =>
		post(buildSnippetUrl(snippet, 'restore')),

	activate: snippet =>
		post<SnippetSchema>(buildSnippetUrl(snippet, 'activate'))
			.then(createSnippetObject),

	deactivate: snippet =>
		post<SnippetSchema>(buildSnippetUrl(snippet, 'deactivate'))
			.then(createSnippetObject),

	export: snippet =>
		get<SnippetsExport>(buildSnippetUrl(snippet, 'export')),

	exportCode: snippet =>
		get<string>(buildSnippetUrl(snippet, 'export-code')),

	attach: snippet =>
		put(buildSnippetUrl(snippet, 'attach'), { condition_id: snippet.conditionId }),

	detach: snippet =>
		put(buildSnippetUrl(snippet, 'detach'))
})

const [Context, useSnippetsAPI] = createContextHook<SnippetsAPI>('useSnippetsAPI')

export const WithSnippetsAPIContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { api } = useRestAPI()

	const value: SnippetsAPI = useMemo(() => buildSnippetsAPI(api), [api])

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetsAPI }
