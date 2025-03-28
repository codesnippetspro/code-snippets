import { isNetworkAdmin } from './screen'
import { Snippet, SNIPPET_TYPE_SCOPES, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

const isAbsInt = (value: unknown): value is number =>
	typeof value === 'number' && value > 0

const isBooleanOrUndefined = (value: unknown): value is boolean | undefined =>
	'boolean' == typeof value || value === undefined

export const isValidScope = (scope: unknown): scope is SnippetScope =>
	'string' === typeof scope &&
	Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
		typeScopes.some(typeScope => typeScope === scope))

export const createSnippetObject = (fields: {} = {}): Snippet => ({
	id: 'id' in fields && isAbsInt(fields.id)
		? fields.id
		: 0,
	name: 'name' in fields && 'string' === typeof fields.name
		? fields.name
		: '',
	desc: 'desc' in fields && 'string' === typeof fields.desc
		? fields.desc
		: '',
	code: 'code' in fields && 'string' === typeof fields.code
		? fields.code
		: '',
	tags: 'tags' in fields && Array.isArray(fields.tags) ?
		fields.tags.filter(value => typeof value === 'string')
		: [],
	scope: 'scope' in fields && isValidScope(fields.scope)
		? fields.scope
		: 'global',
	modified: 'modified' in fields && 'string' === typeof fields.modified
		? fields.modified
		: '',
	active: 'active' in fields && 'boolean' === typeof fields.active
		? fields.active
		: false,
	network: 'network' in fields && isBooleanOrUndefined(fields.network)
		? fields.network
		: isNetworkAdmin(),
	shared_network: 'shared_network' in fields && isBooleanOrUndefined(fields.shared_network)
		? fields.shared_network
		: null,
	priority: 'priority' in fields && typeof fields.priority === 'number'
		? fields.priority
		: 10
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
