export interface Option {
	readonly value: string
	readonly label: string
}

export interface Condition {
	subject: string
	operator: 'eq' | 'neq'
	object: string | number
}

export interface Conditions {
	AND?: Condition[]
	OR?: Condition[]
}
