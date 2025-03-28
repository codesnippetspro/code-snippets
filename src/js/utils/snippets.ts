import { SNIPPET_TYPE_SCOPES } from '../types/Snippet'
import { isNetworkAdmin } from './screen'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

const defaults: Omit<Snippet, 'tags'> = {
	id: 0,
	name: '',
	code: '',
	desc: '',
	scope: 'global',
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10
}

const isAbsInt = (value: unknown): value is number =>
	'number' === typeof value && 0 < value

const isBooleanOrUndefined = (value: unknown): value is boolean | undefined =>
	'boolean' === typeof value || value === undefined

const parseStringArray = (value: unknown): string[] | undefined =>
	Array.isArray(value) ? value.filter(entry => 'string' === typeof entry) : undefined

export const isValidScope = (scope: unknown): scope is SnippetScope =>
	'string' === typeof scope
	&& Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
		typeScopes.some(typeScope => typeScope === scope))

export const createSnippetObject = (fields: unknown = undefined): Snippet => {
	if ('object' !== typeof fields || null === fields) {
		return { ...defaults, tags: [] }
	}

	return {
		id: 'id' in fields && isAbsInt(fields.id) ? fields.id : defaults.id,
		name: 'name' in fields && 'string' === typeof fields.name ? fields.name : defaults.name,
		desc: 'desc' in fields && 'string' === typeof fields.desc ? fields.desc : defaults.desc,
		code: 'code' in fields && 'string' === typeof fields.code ? fields.code : defaults.code,
		tags: 'tags' in fields ? parseStringArray(fields.tags) ?? [] : [],
		scope: 'scope' in fields && isValidScope(fields.scope) ? fields.scope : defaults.scope,
		modified: 'modified' in fields && 'string' === typeof fields.modified ? fields.modified : defaults.modified,
		active: 'active' in fields && 'boolean' === typeof fields.active ? fields.active : defaults.active,
		network: 'network' in fields && isBooleanOrUndefined(fields.network) ? fields.network : defaults.network,
		shared_network: 'shared_network' in fields && isBooleanOrUndefined(fields.shared_network)
			? fields.shared_network : defaults.shared_network,
		priority: 'priority' in fields && 'number' === typeof fields.priority ? fields.priority : defaults.priority
	}
}

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
