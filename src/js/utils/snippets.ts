import { parseSnippetObject } from './objects'
import { isNetworkAdmin } from './screen'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

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

const getSnippetScope = (snippetOrScope: Snippet | SnippetScope): SnippetScope =>
	'string' === typeof snippetOrScope ? snippetOrScope : snippetOrScope.scope

export const getSnippetType = (snippetOrScope: Snippet | SnippetScope): SnippetType => {
	const scope = getSnippetScope(snippetOrScope)

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

export const isCondition = (snippet: Snippet): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Snippet | SnippetScope): boolean =>
	PRO_TYPES.includes(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.includes(type)
