import { addQueryArgs } from '@wordpress/url'
import { __, sprintf } from '@wordpress/i18n'
import { parseSnippetObject } from './objects'
import { isNetworkAdmin } from './screen'
import type { Snippet, SnippetType } from '../types/Snippet'

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

export const getSnippetEditUrl = ({ id }: Pick<Snippet, 'id'>): string =>
	addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id })

export const getSnippetDisplayName = (snippet: Snippet): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(__('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

export const isCondition = (snippet: Pick<Snippet, 'scope'>): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)
