import type { CloudSnippetSchema } from './CloudSnippetSchema'

export interface CloudSnippetsSchema {
	snippets: CloudSnippetSchema[]
	page: number
	total_pages: number
	total_snippets: number
	available_filters?: unknown
}
