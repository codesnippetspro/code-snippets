import type { Condition, ConditionGroup, ConditionRule, ConditionSubject } from '../types/Condition'
import type { Snippet } from '../types/Snippet'

const getNextIndex = (items: Record<PropertyKey, unknown> | undefined) => {
	const keys = items ? Object.keys(items) : []
	return 1 + (keys.length ? Math.max(...keys.map(Number).filter(value => !Number.isNaN(value))) : 0)
}

export const initialiseConditionGroups = (): Condition => ({
	0: {
		0: { subject: 'global' }
	}
})

export const addConditionGroup = (snippet: Snippet): Snippet => ({
	...snippet,
	conditions: {
		...snippet.conditions,
		[getNextIndex(snippet.conditions)]: { 0: {} }
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
	delta: Partial<ConditionRule<ConditionSubject>>
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
				[ruleId]: { ...snippet.conditions[groupId][ruleId], ...delta }
			}
		}
	}
}
