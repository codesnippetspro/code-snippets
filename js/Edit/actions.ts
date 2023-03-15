import { __ } from '@wordpress/i18n'
import { Snippet } from '../types/Snippet'
import { SnippetsAPI } from '../utils/api'

const verifySnippetData = ({ code, name, scope }: Snippet): boolean => {
	const message = 'condition' === scope ? '' :
		!name.trim() ?
			code.trim() ?
				__('This snippet has no title. Continue?', 'code-snippets') :
				__('This snippet has no code or title. Continue?', 'code-snippets') :
			code.trim() ? '' :
				__('This snippet has no snippet code. Continue?', 'code-snippets')

	return '' !== message && !confirm(message)
}

export const saveSnippet = (snippet: Snippet, api: SnippetsAPI) => {
	verifySnippetData(snippet) && api.create(snippet)
}

export const saveAndActivateSnippet = (snippet: Snippet, api: SnippetsAPI, activate: boolean) => {
	if (verifySnippetData(snippet)) {
		snippet.active = activate
		api.create(snippet)
	}
}

export const exportSnippet = (snippet: Snippet, api: SnippetsAPI) => {
	api.export(snippet)
}

export const exportSnippetCode = (snippet: Snippet, api: SnippetsAPI) => {
	api.exportCode(snippet)
}

export const deleteSnippet = (snippet: Snippet, api: SnippetsAPI) => {
	confirm([
		__('You are about to permanently delete this snippet.', 'code-snippets'),
		__("'Cancel' to stop, 'OK' to delete.", 'code-snippets')
	].join('\n')) ?
		api.delete(snippet) :
		undefined
}
