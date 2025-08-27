import type { ConditionSubject } from '../../types/ConditionSubject'
import type { ConditionGroup, ConditionRule } from '../../types/ConditionGroups'
import type { Snippet } from '../../types/Snippet'

const getNextIndex = (items: Record<PropertyKey, unknown> | undefined) => {
	const keys = items
		? Object.keys(items)
			.map(key => Number(key.replaceAll('_', '')))
			.filter(key => !Number.isNaN(key))
		: []

	return `${1 + (keys.length ? Math.max(...keys) : 0)}_`
}

export const getConditionRule = (
	snippet: Snippet,
	groupId: string,
	ruleId: string
): ConditionRule<ConditionSubject> | undefined =>
	snippet.conditions[groupId]?.[ruleId]

export const addConditionGroup = (snippet: Snippet): Snippet => ({
	...snippet,
	conditions: {
		...snippet.conditions,
		[getNextIndex(snippet.conditions)]: { '0_': {} }
	}
})

export const appendConditionRule = (snippet: Snippet, groupId: string, afterRuleId: string): Snippet => {
	const amendedGroup: ConditionGroup = {}

	if (!snippet.conditions[groupId]) {
		console.error('cannot find condition group amend', snippet.conditions, groupId)
		return snippet
	}

	for (const [ruleId, rule] of Object.entries(snippet.conditions[groupId])) {
		amendedGroup[ruleId] = rule

		if (ruleId === afterRuleId) {
			amendedGroup[getNextIndex(snippet.conditions[groupId])] = {}
		}
	}

	return { ...snippet, conditions: { ...snippet.conditions, [groupId]: amendedGroup } }
}

export const cloneConditionRule = (
	snippet: Snippet,
	groupId: string,
	ruleId: string
): Snippet => {
	if (!snippet.conditions[groupId]?.[ruleId]) {
		console.error('cannot find condition rule to clone', snippet.conditions, groupId, ruleId)
		return snippet
	}

	return {
		...snippet,
		conditions: {
			...snippet.conditions,
			[groupId]: {
				...snippet.conditions[groupId],
				[getNextIndex(snippet.conditions[groupId])]: { ...snippet.conditions[groupId][ruleId] }
			}
		}
	}
}

export const removeConditionRule = (snippet: Snippet, groupId: string, ruleId: string): Snippet => {
	if (!snippet.conditions[groupId]?.[ruleId]) {
		console.debug('cannot find condition rule to remove', snippet.conditions, groupId, ruleId)
		return snippet
	}

	const { [ruleId]: condition, ...remaining } = snippet.conditions[groupId]

	return {
		...snippet,
		conditions: {
			...snippet.conditions,
			[groupId]: remaining
		}
	}
}

export const updateConditionRule = (
	snippet: Snippet,
	groupId: string,
	ruleId: string,
	delta: Partial<ConditionRule<ConditionSubject>> |
		((previous: ConditionRule<ConditionSubject>) => Partial<ConditionRule<ConditionSubject>>)
): Snippet => {
	if (!snippet.conditions[groupId]?.[ruleId]) {
		console.debug('cannot find condition rule to update', snippet.conditions, groupId, ruleId)
		return snippet
	}

	return {
		...snippet,
		conditions: {
			...snippet.conditions,
			[groupId]: {
				...snippet.conditions[groupId],
				[ruleId]: {
					...snippet.conditions[groupId][ruleId],
					...'function' === typeof delta ? delta(snippet.conditions[groupId][ruleId]) : delta
				}
			}
		}
	}
}
