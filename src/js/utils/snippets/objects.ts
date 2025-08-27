import { SNIPPET_TYPE_SCOPES } from '../../types/Snippet'
import { isNetworkAdmin } from '../screen'
import type { ConditionGroups } from '../../types/ConditionGroups'
import type { Snippet, SnippetScope } from '../../types/Snippet'

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

const isAbsInt = (value: unknown): value is number =>
	'number' === typeof value && 0 < value

const isValidCondition = (condition: unknown): condition is ConditionGroups =>
	'object' === typeof condition && null !== condition && Object.values(condition)
		.every((group: unknown) => 'object' === typeof group && null !== group &&
			Object.values(group).every((rule: unknown) => 'object' === typeof rule && null !== rule))

const generateObjectKeys = <T>(items: Record<PropertyKey, T>, transformItem?: (item: T) => T): Record<string, T> =>
	Object.fromEntries(
		Object.values(items).map((item, index) =>
			[`_${index}`, transformItem ? transformItem(item) : item]
		))

const parseConditionGroups = (condition: ConditionGroups) =>
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

const parseStringArray = (value: unknown): string[] | undefined =>
	Array.isArray(value) ? value.filter(entry => 'string' === typeof entry) : undefined

export const isValidScope = (scope: unknown): scope is SnippetScope =>
	'string' === typeof scope && Object.values(SNIPPET_TYPE_SCOPES).some(typeScopes =>
		typeScopes.some(typeScope => typeScope === scope))

export const parseSnippetObject = (fields: unknown): Snippet => {
	const result: Snippet = { ...defaults, tags: [], conditions: {} }

	if ('object' !== typeof fields || null === fields) {
		return result
	}

	const parsed: Snippet = {
		...result,
		...'id' in fields && isAbsInt(fields.id) && { id: fields.id },
		...'name' in fields && 'string' === typeof fields.name && { name: fields.name },
		...'desc' in fields && 'string' === typeof fields.desc && { desc: fields.desc },
		...'code' in fields && 'string' === typeof fields.code && { code: fields.code },
		...'tags' in fields && { tags: parseStringArray(fields.tags) ?? result.tags },
		...'scope' in fields && isValidScope(fields.scope) && { scope: fields.scope },
		...'modified' in fields && 'string' === typeof fields.modified && { modified: fields.modified },
		...'active' in fields && 'boolean' === typeof fields.active && { active: fields.active },
		...'network' in fields && 'boolean' === typeof fields.network && { network: fields.network },
		...'shared_network' in fields && 'boolean' === typeof fields.shared_network && { shared_network: fields.shared_network },
		...'priority' in fields && 'number' === typeof fields.priority && { priority: fields.priority },
		...'conditionId' in fields && isAbsInt(fields.conditionId) && { conditionId: fields.conditionId },
		...'condition_id' in fields && isAbsInt(fields.condition_id) && { conditionId: fields.condition_id },
	}

	return { ...parsed, ...parseConditions(parsed, fields) }
}
