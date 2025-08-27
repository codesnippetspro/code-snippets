import type { SnippetScope } from '../Snippet'

export interface WritableSnippetSchema {
	name?: string
	desc?: string
	code?: string
	tags?: string[]
	scope?: SnippetScope
	condition_id?: number
	active?: boolean
	priority?: number
	network?: boolean | null
	shared_network?: boolean | null
}

export interface SnippetSchema extends Readonly<Required<WritableSnippetSchema>> {
	readonly id: number
	readonly modified: string
	readonly code_error?: readonly [string, number] | null
}
