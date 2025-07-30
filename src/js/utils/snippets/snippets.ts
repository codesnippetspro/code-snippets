import { addQueryArgs } from '@wordpress/url'
import { __, sprintf } from '@wordpress/i18n'
import { buildOptionGroups } from '../options'
import { parseSnippetObject } from './objects'
import type { SelectGroup } from '../../types/SelectOption'
import type { Snippet, SnippetType } from '../../types/Snippet'

const PRO_TYPES = new Set<SnippetType>(['css', 'js', 'cond'])

const TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions (PHP)', 'code-snippets'),
	html: __('Content (Mixed)', 'code-snippets'),
	css: __('Styles (CSS)', 'code-snippets'),
	js: __('Scripts (JS)', 'code-snippets'),
	cond: __('Conditions', 'code-snippets')
}

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

export const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingTitle = '' === snippet.name.trim()


	const missingCode = isCondition(snippet)
		? !snippet.conditions
		: '' === snippet.code.trim()

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

export const getSnippetEditUrl = ({ id }: Pick<Snippet, 'id'>): string =>
	addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id })

export const getSnippetDisplayName = (snippet: Pick<Snippet, 'name' | 'id' | 'scope'>): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(isCondition(snippet) ? __('Condition #%d', 'code-snippets') : __('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

export const isCondition = (snippet: Pick<Snippet, 'scope'>): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)

export const buildSnippetSelectOptionGroups = (snippets: Snippet[]): SelectGroup<Snippet>[] =>
	buildOptionGroups({
		items: snippets,
		groups: TYPE_LABELS,
		getGroup: getSnippetType,
		buildOption: snippet => ({
			key: `${snippet.id}-${snippet.network}`,
			value: snippet,
			label: getSnippetDisplayName(snippet)
		})
	})
