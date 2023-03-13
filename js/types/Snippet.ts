import { Conditions } from '../Edit/ConditionEditor/types'

export interface Snippet {
	id: number
	name: string
	desc: string
	code: string
	conditions?: Conditions
	tags: string[]
	scope: SnippetScope
	priority: number
	active: boolean
	network: boolean
	shared_network: boolean
	modified: string
}

export const SNIPPET_SCOPES = <const> [
	'global', 'admin', 'front-end', 'single-use',
	'content', 'head-content', 'footer-content',
	'admin-css', 'site-css',
	'site-head-js', 'site-footer-js',
	'condition'
]

export const SNIPPET_TYPES = <const> ['php', 'html', 'css', 'js', 'cond']

export type SnippetType = typeof SNIPPET_TYPES[number]
export type SnippetScope = typeof SNIPPET_SCOPES[number]

export const SNIPPET_TYPE_SCOPES: Record<SnippetType, SnippetScope[]> = {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js'],
	cond: ['condition']
}
