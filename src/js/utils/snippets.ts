import { createInitialConditionRules } from '../services/edit/conditions/rules'
import { SNIPPET_TYPE_SCOPES } from '../types/Snippet'
import { isNetworkAdmin } from './screen'
import type { ConditionRules } from '../types/ConditionRule'
import type { Snippet, SnippetScope, SnippetType } from '../types/Snippet'

const PRO_TYPES: SnippetType[] = ['css', 'js']

const isAbsInt = (value: unknown): value is number =>
	'number' === typeof value && 0 < value

const parseStringArray = (value: unknown): string[] | undefined =>
	Array.isArray(value) ? value.filter(entry => 'string' === typeof entry) : undefined

export const isValidScope = (scope: unknown): scope is SnippetScope =>
	'string' === typeof scope
	&& Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
		typeScopes.some(typeScope => typeScope === scope))

const isValidConditionRules = (rules: unknown): rules is ConditionRules =>
	'object' === typeof rules && null !== rules && Object.values(rules)
		.every(rule => 'object' === typeof rule && null !== rule)

export const createSnippetObject = (fields: unknown = undefined): Snippet => {
	const defaults: Snippet = {
		id: 0,
		name: '',
		code: '',
		desc: '',
		tags: [],
		scope: 'global',
		modified: '',
		active: false,
		network: isNetworkAdmin(),
		shared_network: null,
		priority: 10,
		conditional: 0,
		conditions: {}
	}

	if ('object' !== typeof fields || null === fields) {
		return { ...defaults, conditions: createInitialConditionRules() }
	}

	const parsed: Snippet = {
		id: 'id' in fields && isAbsInt(fields.id) ? fields.id : defaults.id,
		name: 'name' in fields && 'string' === typeof fields.name ? fields.name : defaults.name,
		desc: 'desc' in fields && 'string' === typeof fields.desc ? fields.desc : defaults.desc,
		code: 'code' in fields && 'string' === typeof fields.code ? fields.code : defaults.code,
		tags: 'tags' in fields ? parseStringArray(fields.tags) ?? defaults.tags : defaults.tags,
		scope: 'scope' in fields && isValidScope(fields.scope) ? fields.scope : defaults.scope,
		modified: 'modified' in fields && 'string' === typeof fields.modified ? fields.modified : defaults.modified,
		active: 'active' in fields && 'boolean' === typeof fields.active ? fields.active : defaults.active,
		network: 'network' in fields && 'boolean' === typeof fields.network ? fields.network : defaults.network,
		shared_network: 'shared_network' in fields && 'boolean' === typeof fields.shared_network && fields.shared_network
			|| defaults.shared_network,
		priority: 'priority' in fields && 'number' === typeof fields.priority ? fields.priority : defaults.priority,
		conditional: 'conditional' in fields && isAbsInt(fields.conditional) ? fields.conditional : defaults.conditional,
		conditions: 'conditions' in fields && isValidConditionRules(fields.conditions) ? fields.conditions : defaults.conditions
	}

	if ('condition' === parsed.scope && '' !== parsed.code.trim() && 0 === Object.keys(parsed.conditions).length) {
		try {
			const parsedRules: unknown = JSON.parse(parsed.code)

			if (isValidConditionRules(parsedRules)) {
				parsed.conditions = parsedRules
				parsed.code = defaults.code
			}
		} catch (error) {
			console.error('Failed to parse condition rules JSON.', parsed.code, error)
		}
	}

	return parsed
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
