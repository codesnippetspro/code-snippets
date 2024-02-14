import { Snippet, SnippetScope, SnippetType } from '../types/Snippet'
import { isNetworkAdmin } from './general'

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

export const getSnippetType = (snippet: Snippet | SnippetScope): SnippetType => {
	const scope = 'string' === typeof snippet ? snippet : snippet.scope

	if (scope.endsWith('-css')) {
		return 'css'
	}

	if (scope.endsWith('-js')) {
		return 'js'
	}

	if (scope.endsWith('content')) {
		return 'html'
	}

	return 'php'
}

export const isProSnippet = (snippet: Snippet | SnippetScope): boolean =>
	PRO_TYPES.includes(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.includes(type)

export const isLicensed = (): boolean =>
	!!window.CODE_SNIPPETS?.isLicensed
