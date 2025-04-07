import type { Condition } from '../Condition'
import type { SnippetScope } from '../Snippet'

export interface SnippetSchema {
	readonly id: number
	readonly name: string
	readonly desc: string
	readonly code: string
	readonly tags: readonly string[]
	readonly scope: SnippetScope
	readonly priority: number
	readonly active: boolean
	readonly network: boolean
	readonly shared_network?: boolean | null
	readonly modified?: string
	readonly condition_id?: number
	readonly code_error?: readonly [string, number] | null
	readonly conditions: Condition
}
