import { Snippet } from './Snippet'

export interface ExportSnippets {
	generator: string
	date_created: string
	snippets: Array<Snippet>
}
