import type { ConditionGroups } from './Condition'

export interface Snippet {
	id: number
	name: string
	desc: string
	code: string
	conditions?: ConditionGroups
	tags: string[]
	scope: SnippetScope
	conditional: number
	priority: number
	active: boolean
	network?: boolean
	shared_network?: boolean | null
	modified?: string
	code_error?: [string, number] | null
}

export type SnippetCodeType = 'php' | 'html' | 'css' | 'js'
export type SnippetType = SnippetCodeType | 'cond'

export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use', 'conditional-php'],
	html: ['content', 'head-content', 'footer-content', 'conditional-html'],
	css: ['admin-css', 'site-css', 'conditional-css'],
	js: ['site-head-js', 'site-footer-js', 'conditional-js'],
	cond: ['condition']
}
