import { __ } from '@wordpress/i18n'
import React from 'react'
import { useInsightsChartViews } from '../../hooks/useInsightsChartViews'
import {
	INSIGHTS_ACTIVATION_COLORS,
	INSIGHTS_LOCATION_COLORS,
	INSIGHTS_TYPE_COLORS,
	InsightsChart
} from './InsightsCharts'
import type { InsightsChartDefinition, InsightsSummary } from '../../types/Insights'

interface InsightsDashboardProps {
	summary: InsightsSummary
}

export const InsightsDashboard: React.FC<InsightsDashboardProps> = ({ summary }) => {
	const { chartViews, setChartView } = useInsightsChartViews()
	const activationEntries = {
		active: { label: __('Active', 'code-snippets'), count: summary.active },
		inactive: { label: __('Inactive', 'code-snippets'), count: summary.inactive }
	}
	const chartDefinitions: readonly InsightsChartDefinition[] = [
		{
			key: 'type',
			title: __('Snippet type', 'code-snippets'),
			entries: summary.typeCounts,
			colors: INSIGHTS_TYPE_COLORS
		},
		{
			key: 'activation',
			title: __('Activation status', 'code-snippets'),
			entries: activationEntries,
			colors: INSIGHTS_ACTIVATION_COLORS
		},
		{
			key: 'location',
			title: __('Location', 'code-snippets'),
			entries: summary.locationCounts,
			colors: INSIGHTS_LOCATION_COLORS
		}
	]

	return <>
		<div className="snippets-page-header">
			<h1>{__('Insights', 'code-snippets')}</h1>
		</div>

		<hr className="wp-header-end"></hr>

		<section className="insights-chart-grid">
			{chartDefinitions.map(({ colors, entries, key, title }) =>
				<InsightsChart
					key={key}
					chart={key}
					title={title}
					entries={entries}
					view={chartViews[key]}
					setView={view => setChartView(key, view)}
					colors={colors}
				/>) }
		</section>
	</>
}
