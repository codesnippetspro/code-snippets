import { createInitialConditionRules } from '../services/edit/conditions/rules'
import { ConditionRules } from '../types/ConditionRule'
import { isNetworkAdmin } from './screen'
import { Snippet, SNIPPET_TYPE_SCOPES, SnippetScope, SnippetType } from '../types/Snippet'

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
	if (typeof scope === 'string') {
		Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
			typeScopes.some(typeScope => typeScope === scope)
		)
	}

	return false
}

export const parseSnippetObject = (data: unknown): Snippet => {
	const fallback = createEmptySnippet()

	if (typeof data !== 'object' || data === null) {
		return fallback
	}

	return {
		id: 'id' in data && typeof data.id === 'number' ? data.id : fallback.id,
		name: 'name' in data && typeof data.name === 'string' ? data.name : fallback.name,
		desc: 'desc' in data && typeof data.desc === 'string' ? data.desc : fallback.desc,
		code: 'code' in data && typeof data.code === 'string' ? data.code : fallback.code,
		tags: 'tags' in data && Array.isArray(data.tags) ? data.tags : fallback.tags,
		scope: 'scope' in data && isValidScope(data.scope) ? data.scope : fallback.scope,
		conditional: 'conditional' in data && typeof data.conditional === 'number' ? data.conditional : fallback.conditional,
		modified: 'modified' in data && typeof data.modified === 'string' ? data.modified : fallback.modified,
		active: 'active' in data && typeof data.active === 'boolean' ? data.active : fallback.active,
		network: 'network' in data && typeof data.network === 'boolean' ? data.network : fallback.network,
		shared_network: 'shared_network' in data && typeof data.shared_network === 'boolean' ? data.shared_network : fallback.shared_network,
		priority: 'priority' in data && typeof data.priority === 'number' ? data.priority : fallback.priority,
		conditions: 'conditions' in data && data.conditions !== null && data.conditions !== undefined ? data.conditions as ConditionRules : fallback.conditions
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
