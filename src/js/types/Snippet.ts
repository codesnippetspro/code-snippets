export interface Snippet {
	id: number
	name: string
	desc: string
	code: string
	tags: string[]
	scope: SnippetScope
	priority: number
	active: boolean
	network?: boolean
	shared_network?: boolean | null
	modified?: string
	code_error?: [string, number] | null
}

export type SnippetType = 'php' | 'html' | 'css' | 'js'

export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js']
}
