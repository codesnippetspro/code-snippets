import { __ } from '@wordpress/i18n'
import { Snippet } from '../types/Snippet'

export const saveSnippet = (snippet: Snippet) => {
	const message = 'condition' === snippet.scope ? '' :
		!snippet.name.trim() ?
			snippet.code.trim() ?
				__('This snippet has no title. Continue?', 'code-snippets') :
				__('This snippet has no code or title. Continue?', 'code-snippets') :
			snippet.code.trim() ? '' :
				__('This snippet has no snippet code. Continue?', 'code-snippets')

	if (message && !confirm(message)) {
		return
	}

	console.error('Save snippet not implemented.', snippet)
}

export const saveSnippetActivate = (snippet: Snippet) => {
	snippet.active = true
	saveSnippet(snippet)
}

export const saveSnippetDeactivate = (snippet: Snippet) => {
	snippet.active = false
	saveSnippet(snippet)
}

export const downloadSnippet = (snippet: Snippet) =>
	console.error('Download snippet not implemented.', snippet)

export const deleteSnippet = (snippet: Snippet) =>
	console.error('Delete snippet not implemented.', snippet)

export const exportSnippet = (snippet: Snippet) =>
	console.error('Export snippet not implemented.', snippet)
