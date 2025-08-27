export interface Snippet {
	readonly id: number
	readonly name: string
	readonly desc: string
	readonly code: string
	readonly tags: string[]
	readonly scope: SnippetScope
	readonly priority: number
	readonly active: boolean
	readonly network: boolean
	readonly shared_network?: boolean | null
	readonly modified?: string
	readonly conditionId: number
	readonly code_error?: readonly [string, number] | null
}

export type SnippetCodeType = 'php' | 'html' | 'css' | 'js'
export type SnippetType = SnippetCodeType | 'cond'

export type SnippetCodeScope = typeof SNIPPET_TYPE_SCOPES[SnippetCodeType][number]
export type SnippetScope = typeof SNIPPET_TYPE_SCOPES[SnippetType][number]

export const SNIPPET_TYPE_SCOPES = <const> {
	php: ['global', 'admin', 'front-end', 'single-use'],
	html: ['content', 'head-content', 'footer-content'],
	css: ['admin-css', 'site-css'],
	js: ['site-head-js', 'site-footer-js'],
	cond: ['condition']
}
