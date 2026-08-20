import type { SnippetScope, SnippetType } from './Snippet'

export type InsightsChartKey = 'total' | InsightsConfigurableChartKey | 'tags'

export type InsightsConfigurableChartKey = 'type' | 'activation' | 'location'

export type InsightsChartView = 'pie' | 'bar'

export type InsightsChartViews = Readonly<Record<InsightsConfigurableChartKey, InsightsChartView>>

export interface InsightsChartEntry {
	readonly label: string
	readonly count: number | string
}

export interface InsightsSummary {
	readonly active: number | string
	readonly inactive: number | string
	readonly typeCounts: Readonly<Record<SnippetType, InsightsChartEntry>>
	readonly locationCounts: Readonly<Record<SnippetScope, number>>
	readonly tagCounts: Readonly<Record<string, InsightsChartEntry>>
}

export interface InsightChartPreferencesSchema {
	views: InsightsChartViews
}
