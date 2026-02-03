import { __, sprintf } from '@wordpress/i18n'
import { buildUrl } from '../urls'
import { parseSnippetObject } from './objects'
import type { Snippet, SnippetScope, SnippetType } from '../../types/Snippet'

export const SNIPPET_TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions', 'code-snippets'),
	html: __('Content', 'code-snippets'),
	css: __('Styles', 'code-snippets'),
	js: __('Scripts', 'code-snippets'),
	cond: __('Conditions', 'code-snippets')
}

const PRO_TYPES = new Set<SnippetType>(['css', 'js', 'cond'])

export const createSnippetObject = (fields: unknown): Snippet =>
	parseSnippetObject(fields)

export const getSnippetType = ({ scope }: Pick<Snippet, 'scope'>): SnippetType => {
	switch (true) {
		case scope.endsWith('-css'):
			return 'css'

		case scope.endsWith('-js'):
			return 'js'

		case scope.endsWith('content'):
			return 'html'

		case 'condition' === scope:
			return 'cond'

		default:
			return 'php'
	}
}

export const getSnippetEditUrl = (snippet?: Pick<Snippet, 'id'>): string | undefined =>
	snippet?.id
		? buildUrl(window.CODE_SNIPPETS?.urls.edit, { id: snippet.id })
		: window.CODE_SNIPPETS?.urls.addNew

export const getSnippetDisplayName = (snippet: Pick<Snippet, 'name' | 'id' | 'scope'>): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(isCondition(snippet) ? __('Condition #%d', 'code-snippets') : __('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

export const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingTitle = '' === snippet.name.trim()
	const missingCode = '' === snippet.code.trim()

	switch (true) {
		case missingCode && missingTitle:
			return __('This snippet has no code or title.', 'code-snippets')

		case missingCode:
			return __('This snippet has no snippet code.', 'code-snippets')

		case missingTitle:
			return __('This snippet has no title.', 'code-snippets')

		default:
			return undefined
	}
}

export const isCondition = (snippet: { scope?: SnippetScope }): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)
