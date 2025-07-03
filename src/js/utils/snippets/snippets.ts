import { __ } from '@wordpress/i18n'
import { isNetworkAdmin } from '../screen'
import { parseSnippetObject } from './objects'
import type { Snippet, SnippetType } from '../../types/Snippet'

const PRO_TYPES = new Set<SnippetType>(['css', 'js'])

const defaults: Omit<Snippet, 'tags' | 'conditions'> = {
	id: 0,
	name: '',
	code: '',
	desc: '',
	scope: 'global',
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10,
	conditionId: 0
}

export const createSnippetObject = (fields: unknown = null): Snippet =>
	parseSnippetObject(fields, { ...defaults, tags: [], conditions: {} })

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

export const isCondition = (snippet: Pick<Snippet, 'scope'>): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)
