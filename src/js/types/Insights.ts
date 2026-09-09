import type { SnippetScope, SnippetType } from './Snippet'

export type InsightsChartKey = 'total' | InsightsConfigurableChartKey

export type InsightsConfigurableChartKey = 'type' | 'activation' | 'conditions' | 'location' | 'tags'

export type InsightsChartView = 'pie' | 'bar' | 'cloud'

export type InsightsChartViews = Readonly<{
	type: 'pie' | 'bar'
	activation: 'pie' | 'bar'
	conditions: 'pie' | 'bar'
	location: 'pie' | 'bar'
	tags: 'bar' | 'cloud'
}>

export interface InsightsChartEntry {
	readonly label: string
	readonly count: number | string
	readonly url?: string
}

export interface InsightsSummary {
	readonly active: number | string
	readonly inactive: number | string
	readonly typeCounts: Readonly<Record<SnippetType, InsightsChartEntry>>
	readonly conditionCounts: Readonly<Record<string, InsightsChartEntry>>
	readonly locationCounts: Readonly<Record<SnippetScope, number>>
	readonly tagCounts: Readonly<Record<string, InsightsChartEntry>>
}

export interface InsightChartPreferencesSchema {
	views: InsightsChartViews
}
