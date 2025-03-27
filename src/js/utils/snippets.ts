import { createInitialConditionRules } from '../services/edit/conditions/rules'
import { SNIPPET_TYPE_SCOPES } from '../types/Snippet'
import { isNetworkAdmin } from './screen'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'
import type { ConditionRules } from '../types/ConditionRule'

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
	priority: 10,
	conditions: createInitialConditionRules()
})

export const isValidScope = (scope: unknown): scope is SnippetScope => {
	if ('string' === typeof scope) {
		Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
			typeScopes.some(typeScope => typeScope === scope)
		)
	}

	return false
}

export const parseSnippetObject = (data: unknown): Snippet => {
	const fallback = createEmptySnippet()

	if ('object' !== typeof data || null === data) {
		return fallback
	}

	return {
		id: 'id' in data && 'number' === typeof data.id ? data.id : fallback.id,
		name: 'name' in data && 'string' === typeof data.name ? data.name : fallback.name,
		desc: 'desc' in data && 'string' === typeof data.desc ? data.desc : fallback.desc,
		code: 'code' in data && 'string' === typeof data.code ? data.code : fallback.code,
		tags: 'tags' in data && Array.isArray(data.tags)
			? data.tags.map((tag: unknown) => 'string' === typeof tag ? tag : '').filter(Boolean)
			: fallback.tags,
		scope: 'scope' in data && isValidScope(data.scope) ? data.scope : fallback.scope,
		conditional: 'conditional' in data && 'number' === typeof data.conditional ? data.conditional : fallback.conditional,
		modified: 'modified' in data && 'string' === typeof data.modified ? data.modified : fallback.modified,
		active: 'active' in data && 'boolean' === typeof data.active ? data.active : fallback.active,
		network: 'network' in data && 'boolean' === typeof data.network ? data.network : fallback.network,
		shared_network: 'shared_network' in data && 'boolean' === typeof data.shared_network
			? data.shared_network
			: fallback.shared_network,
		priority: 'priority' in data && 'number' === typeof data.priority ? data.priority : fallback.priority,
		conditions: 'conditions' in data && null !== data.conditions && data.conditions !== undefined
			? <ConditionRules> data.conditions
			: fallback.conditions
	}
}

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

export const isCondition = (snippetOrScope: Snippet | SnippetScope): boolean =>
	'condition' === getSnippetScope(snippetOrScope)

export const isProSnippet = (snippet: Snippet | SnippetScope): boolean =>
	PRO_TYPES.includes(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.includes(type)
