import type { ConditionRules } from './ConditionRule'

export interface Snippet {
	id: number
	name: string
	desc: string
	code: string
	tags: string[]
	scope: SnippetScope
	conditional: number
	priority: number
	active: boolean
	network?: boolean
	shared_network?: boolean | null
	modified?: string
	code_error?: [string, number] | null
	conditions: ConditionRules
}

export type SnippetCodeType = 'php' | 'html' | 'css' | 'js'
export type SnippetType = SnippetCodeType | 'cond'

export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js'],
	cond: ['condition']
}
