import { isNetworkAdmin } from './screen'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

export const createEmptySnippet = (): Snippet => ({
	id: 0,
	name: '',
	desc: '',
	code: '',
	tags: [],
	scope: 'global',
	conditional: 0,
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10
})

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

export const getConditionalScope = (snippetOrScope: Snippet | SnippetScope): SnippetScope => {
	const snippetType = getSnippetType(snippetOrScope)
	return 'cond' === snippetType ? 'condition' : `conditional-${snippetType}`
}

export const isCondition = (snippetOrScope: Snippet | SnippetScope): boolean =>
	'condition' === getSnippetScope(snippetOrScope)

export const isProSnippet = (snippet: Snippet | SnippetScope): boolean =>
	PRO_TYPES.includes(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.includes(type)
