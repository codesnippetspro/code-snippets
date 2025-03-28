export interface Snippet {
	readonly id: number
	readonly name: string
	readonly desc: string
	readonly code: string
	readonly tags: string[]
	readonly scope: SnippetScope
	readonly priority: number
	readonly active: boolean
	readonly network?: boolean
	readonly shared_network?: boolean | null
	readonly modified?: string
	readonly code_error?: [string, number] | null
}

export type SnippetType = 'php' | 'html' | 'css' | 'js'

export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js']
}
