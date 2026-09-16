import type { SnippetScope } from '../Snippet'

export interface WritableSnippetSchema {
	name?: string
	desc?: string
	code?: string
	tags?: string[]
	scope?: SnippetScope
	condition_id?: number
	active?: boolean
	locked?: boolean
	trashed?: boolean
	priority?: number
	network?: boolean | null
	shared_network?: boolean | null
}

export interface SnippetSchema extends Readonly<Required<WritableSnippetSchema>> {
	readonly id: number
	readonly modified: string
	readonly last_active?: number
	readonly trashed: boolean
	readonly code_error?: readonly [string, number] | null
	readonly code_error_trace?: string | null
}
