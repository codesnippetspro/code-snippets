
export type ConditionGroups = Record<string, ConditionGroup | undefined>
export type ConditionGroup = Record<string, ConditionRule | undefined>

export interface ConditionRule {
	readonly subject?: string
	readonly operator?: string
	readonly object?: string
}
