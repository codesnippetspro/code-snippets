import { isNetworkAdmin } from './general'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

export const createEmptySnippet = (): Snippet => ({
	id: 0,
	name: '',
	desc: '',
	code: '',
	tags: [],
	scope: 'global',
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10
})

export const getSnippetType = (snippetOrScope: Snippet | SnippetScope): SnippetType => {
	const scope = 'string' === typeof snippetOrScope ? snippetOrScope : snippetOrScope.scope

	switch (true) {
		case scope.endsWith('-css'):
			return 'css'

		case scope.endsWith('-js'):
			return 'js'

		case scope.endsWith('content'):
			return 'html'

		default:
			return 'php'
	}
}

export const isProSnippet = (snippet: Snippet | SnippetScope): boolean =>
	PRO_TYPES.includes(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.includes(type)
