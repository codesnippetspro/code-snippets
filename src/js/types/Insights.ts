export const INSIGHTS_CHART_KEYS = <const> ['type', 'activation', 'location', 'tags']

export type InsightsChartKey = typeof INSIGHTS_CHART_KEYS[number]

export const INSIGHTS_CONFIGURABLE_CHART_KEYS = <const> ['type', 'activation', 'location']

export type InsightsConfigurableChartKey = typeof INSIGHTS_CONFIGURABLE_CHART_KEYS[number]

export const INSIGHTS_CHART_VIEWS = <const> ['pie', 'bar']

export type InsightsChartView = typeof INSIGHTS_CHART_VIEWS[number]

export type InsightsChartViews = Readonly<Record<InsightsConfigurableChartKey, InsightsChartView>>

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
	readonly view: InsightsChartView
	readonly setView?: (view: InsightsChartView) => void
}

export interface InsightsSummary {
	readonly active: number | string
	readonly inactive: number | string
	readonly typeCounts: Readonly<Record<string, InsightsChartEntry>>
	readonly locationCounts: Readonly<Record<string, InsightsChartEntry>>
	readonly tagCounts: Readonly<Record<string, InsightsChartEntry>>
}
