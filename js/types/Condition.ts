export interface Condition {
	subject: ConditionSubject
	operator: ConditionOperator
	object: string | number
}

export type ConditionGroups = {
	AND?: ConditionGroup
	OR?: ConditionGroup
}

export type ConditionGroup = Record<string, Condition>

export type ConditionSubject =
	'post' | 'page' | 'postType' | 'category' | 'tag' |
	'user' | 'authenticated' | 'userRole'

export type ConditionOperator = 'eq' | 'neq'
