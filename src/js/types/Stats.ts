import type { SnippetScope, SnippetType } from './Snippet'

export type StatsChartKey = 'total' | StatsConfigurableChartKey

export type StatsConfigurableChartKey = 'type' | 'activation' | 'conditions' | 'location' | 'tags'

export type StatsChartView = 'pie' | 'bar' | 'cloud'

export type StatsChartViews = Readonly<{
	type: 'pie' | 'bar'
	activation: 'pie' | 'bar'
	conditions: 'pie' | 'bar'
	location: 'pie' | 'bar'
	tags: 'bar' | 'cloud'
}>

export interface StatsChartEntry {
	readonly label: string
	readonly count: number | string
	readonly url?: string
}

export interface StatsSummary {
	readonly active: number | string
	readonly inactive: number | string
	readonly typeCounts: Readonly<Record<SnippetType, StatsChartEntry>>
	readonly conditionCounts: Readonly<Record<string, StatsChartEntry>>
	readonly locationCounts: Readonly<Record<SnippetScope, number>>
	readonly tagCounts: Readonly<Record<string, StatsChartEntry>>
}

export interface StatsChartPreferencesSchema {
	views: StatsChartViews
}
