import type { SnippetSchema } from './SnippetSchema'

export interface SnippetsExport {
	generator: string
	date_created: string
	snippets: Partial<SnippetSchema>[]
}
