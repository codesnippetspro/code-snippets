import type { SnippetScope } from '../Snippet'

export interface SnippetSchema {
	readonly id: number
	name: string
	desc: string
	code: string
	tags: string[]
	scope: SnippetScope
	priority: number
	active: boolean
	network: boolean
	condition_id?: number
	shared_network?: boolean | null
	readonly modified?: string
	readonly code_error?: readonly [string, number] | null
}
