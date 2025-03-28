import type { ConditionRule, ConditionRules } from '../types/ConditionRule'
import type { Snippet } from '../types/Snippet'

const getNextIndex = (items: Record<PropertyKey, unknown> | undefined) => {
	const keys = items ? Object.keys(items) : []
	return 1 + (keys.length ? Math.max(...keys.map(Number).filter(value => !Number.isNaN(value))) : 0)
}

export const createInitialConditionRules = (): ConditionRules => ({
	0: { enabled: true, subject: 'global' }
})

export const addConditionRule = (snippet: Snippet): Snippet => ({
	...snippet,
	conditions: {
		...snippet.conditions,
		[getNextIndex(snippet.conditions)]: {}
	}
})

export const cloneConditionRule = (
	snippet: Snippet,
	ruleId: string
): Snippet => {
	if (!snippet.conditions[ruleId]) {
		console.log('cannot find condition rule to clone', ruleId)
		return snippet
	}

	return {
		...snippet,
		conditions: {
			...snippet.conditions,
			[getNextIndex(snippet.conditions)]: { ...snippet.conditions[ruleId] }
		}
	}
}

export const removeConditionRule = (snippet: Snippet, ruleId: string): Snippet => {
	if (!snippet.conditions[ruleId]) {
		console.log('cannot find condition rule to remove', ruleId)
		return snippet
	}

	const { [ruleId]: condition, ...remaining } = snippet.conditions
	return { ...snippet, conditions: remaining }
}

export const updateConditionRule = (
	snippet: Snippet,
	ruleId: string,
	delta: Partial<ConditionRule>
): Snippet => ({
	...snippet,
	conditions: {
		...snippet.conditions,
		[ruleId]: { ...snippet.conditions[ruleId], ...delta }
	}
})

export const updateConditionField = <F extends keyof ConditionRule>(
	snippet: Snippet,
	ruleId: string,
	field: F,
	value: ConditionRule[F]
): Snippet =>
	updateConditionRule(snippet, ruleId, { [field]: value })
