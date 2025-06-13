import { _x } from '@wordpress/i18n'
import type { ConditionSubject, ConditionSubjects } from './ConditionSubject'

export type ConditionGroups = Record<string, ConditionGroup | undefined>
export type ConditionGroup = Record<string, ConditionRule<ConditionSubject> | undefined>

export interface ConditionRule<S extends ConditionSubject> {
	readonly subject?: S
	readonly operator?: ConditionOperator
	readonly object?: ConditionSubjects[S][]
}

export const CONDITION_OPERATOR_LABELS = <const> {
	'is': _x('is', 'condition operator', 'code-snippets'),
	'not': _x('is not', 'condition operator', 'code-snippets'),
	'in': _x('in', 'condition operator', 'code-snippets'),
	'not in': _x('not in', 'condition operator', 'code-snippets'),
	'before': _x('before', 'condition operator', 'code-snippets'),
	'after': _x('after', 'condition operator', 'code-snippets'),
	'between': _x('between', 'condition operator', 'code-snippets'),
	'true': _x('is true', 'condition operator', 'code-snippets'),
	'false': _x('is false', 'condition operator', 'code-snippets')
}

export type ConditionOperator = keyof typeof CONDITION_OPERATOR_LABELS
