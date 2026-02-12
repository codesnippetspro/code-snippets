export interface ImportableSnippetSchema {
	id?: number
	name: string
	desc?: string
	description?: string
	code: string
	tags?: string[]
	scope?: string
	source_file?: string
	table_data: {
		id: number | string
		title: string
		scope: string
		tags: string
		description: string
		type: string
	}
}
