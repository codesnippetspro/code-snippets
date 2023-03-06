export type SnippetType = 'css' | 'js' | 'php' | 'html'

export interface Snippet {
	id: number
	name: string
	scope: string
	active: boolean
	network: boolean
	shared_network: boolean
	priority: number
	type: SnippetType
}

export interface SnippetData {
	id: number
	name: string
	code: string
	active: boolean
	type: SnippetType
}
