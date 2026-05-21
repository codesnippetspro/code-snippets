export interface Snippet {
	readonly id: number
	readonly name: string
	readonly desc: string
	readonly code: string
	readonly tags: string[]
	readonly scope: SnippetScope
	readonly priority: number
	readonly active: boolean
	readonly locked: boolean
	readonly trashed: boolean
	readonly network: boolean
	readonly shared_network?: boolean | null
	readonly modified?: string
	readonly conditionId: number
	readonly lastActive?: number
	readonly code_error?: readonly [string, number] | null
	readonly code_error_trace?: string | null
}

export const SNIPPET_TYPES = <const> ['php', 'html', 'css', 'js', 'cond']
export type SnippetType = typeof SNIPPET_TYPES[number]

export type SnippetCodeType = 'php' | 'html' | 'css' | 'js'

export type SnippetCodeScope = typeof SNIPPET_TYPE_SCOPES[SnippetCodeType][number]
export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'body-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js'],
	cond: ['condition']
}
