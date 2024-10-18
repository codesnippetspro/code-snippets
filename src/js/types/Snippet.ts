import type { ConditionGroups } from './Condition'

export interface Snippet {
	id: number
	name: string
	desc: string
	code: string
	conditions?: ConditionGroups
	tags: string[]
	scope: SnippetScope
	priority: number
	active: boolean
	network?: boolean
	shared_network?: boolean | null
	conditionalId?: number
	modified?: string
	code_error?: [string, number] | null
}

export type SnippetType = keyof typeof SNIPPET_TYPE_SCOPES

export type SnippetScope =
	typeof SNIPPET_TYPE_SCOPES['php'][number] |
	typeof SNIPPET_TYPE_SCOPES['html'][number] |
	typeof SNIPPET_TYPE_SCOPES['css'][number] |
	typeof SNIPPET_TYPE_SCOPES['js'][number] |
	typeof SNIPPET_TYPE_SCOPES['cond'][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use', 'conditional-php'],
	html: ['content', 'head-content', 'footer-content', 'conditional-html'],
	css: ['admin-css', 'site-css', 'conditional-css'],
	js: ['site-head-js', 'site-footer-js', 'conditional-js'],
	cond: ['condition']
}

export const SNIPPET_TYPES = <readonly SnippetType[]> Object.keys(SNIPPET_TYPE_SCOPES)
