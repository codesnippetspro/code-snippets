export const INSIGHTS_CHART_KEYS = <const> ['type', 'activation', 'location']

export type InsightsChartKey = typeof INSIGHTS_CHART_KEYS[number]

export const INSIGHTS_CHART_VIEWS = <const> ['pie', 'bar']

export type InsightsChartView = typeof INSIGHTS_CHART_VIEWS[number]

export type InsightsChartViews = Readonly<Record<InsightsChartKey, InsightsChartView>>

export const DEFAULT_INSIGHTS_CHART_VIEWS: InsightsChartViews = {
	type: 'bar',
	activation: 'pie',
	location: 'bar'
}

export interface InsightsChartEntry {
	readonly label: string
	readonly count: number | string
}

export interface InsightsChartDefinition {
	readonly key: InsightsChartKey
	readonly title: string
	readonly entries: Readonly<Record<string, InsightsChartEntry>>
	readonly colors: Readonly<Record<string, string>>
}

export interface InsightsSummary {
	readonly active: number | string
	readonly inactive: number | string
	readonly typeCounts: Readonly<Record<string, InsightsChartEntry>>
	readonly locationCounts: Readonly<Record<string, InsightsChartEntry>>
}
