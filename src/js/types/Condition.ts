export interface Condition {
	subject?: ConditionSubject
	operator?: ConditionOperator
	object?: string | number | boolean
}

export type ConditionGroup = Record<string, Condition>
export type ConditionGroups = Record<string, ConditionGroup>

export type ConditionSubject =
	'post' | 'page' | 'postType' | 'category' | 'tag' |
	'user' | 'authenticated' | 'userRole'

export type ConditionOperator = 'eq' | 'neq'
