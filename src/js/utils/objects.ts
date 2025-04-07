import { SNIPPET_TYPE_SCOPES } from '../types/Snippet'
import type { Snippet, SnippetScope } from '../types/Snippet'
import type { Condition } from '../types/Condition'

const isAbsInt = (value: unknown): value is number =>
	'number' === typeof value && 0 < value

const parseStringArray = (value: unknown): string[] | undefined =>
	Array.isArray(value) ? value.filter(entry => 'string' === typeof entry) : undefined

export const isValidScope = (scope: unknown): scope is SnippetScope =>
	'string' === typeof scope && Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
		typeScopes.some(typeScope => typeScope === scope))

const isValidCondition = (condition: unknown): condition is Condition =>
	'object' === typeof condition && null !== condition && Object.values(condition)
		.every((group: unknown) => 'object' === typeof group && null !== group
			&& Object.values(group).every((rule: unknown) => 'object' === typeof rule && null !== rule))

const generateObjectKeys = <T>(items: Record<PropertyKey, T>, transformItem?: (item: T) => T): Record<string, T> =>
	Object.fromEntries(
		Object.values(items).map((item, index) =>
			[`_${index}`, transformItem ? transformItem(item) : item]
		))

const parseConditionGroups = (condition: Condition) =>
	generateObjectKeys(condition, group => group && generateObjectKeys(group))

const parseConditions = (parsed: Snippet, fields: object): Partial<Snippet> => {
	if ('conditions' in fields && isValidCondition(fields.conditions)) {
		return { conditions: parseConditionGroups(fields.conditions) }
	}

	if ('condition' === parsed.scope && '' !== parsed.code.trim()) {
		try {
			const parsedRules: unknown = JSON.parse(parsed.code)

			if (isValidCondition(parsedRules)) {
				return { conditions: parseConditionGroups(parsedRules), code: '' }
			}
		} catch (error) {
			console.error('Failed to parse condition rules JSON.', parsed.code, error)
		}
	}

	return {}
}

export const parseSnippetObject = (fields: unknown, defaults: Snippet): Snippet => {
	if ('object' !== typeof fields || null === fields) {
		return defaults
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
		conditionId: 'condition_id' in fields && isAbsInt(fields.condition_id) ? fields.condition_id
			: 'conditionId' in fields && isAbsInt(fields.conditionId) ? fields.conditionId : defaults.conditionId,
		conditions: defaults.conditions
	}

	return { ...parsed, ...parseConditions(parsed, fields) }
}
