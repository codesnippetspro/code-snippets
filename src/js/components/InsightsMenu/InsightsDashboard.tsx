import { __ } from '@wordpress/i18n'
import React from 'react'
import { useInsightsChartViews } from '../../hooks/useInsightsChartViews'
import { SNIPPET_SCOPE_DESCRIPTIONS } from '../../utils/snippets/snippets'
import { INSIGHTS_ACTIVATION_COLORS, INSIGHTS_LOCATION_COLORS, INSIGHTS_TYPE_COLORS, InsightsChart } from './InsightsCharts'
import type { InsightsChartDefinition, InsightsChartEntry, InsightsSummary } from '../../types/Insights'
import type { UseInsightsChartViews } from '../../hooks/useInsightsChartViews'
import type { SnippetCodeScope } from '../../types/Snippet'

interface InsightsDashboardProps {
	summary: InsightsSummary
}

interface InsightsChartDefinitionsProps {
	chartViews: UseInsightsChartViews['chartViews']
	setChartView: UseInsightsChartViews['setChartView']
	summary: InsightsSummary
}

const getTotalChartDefinition = (summary: InsightsSummary): InsightsChartDefinition => ({
	key: 'total',
	title: __('Total snippets', 'code-snippets'),
	entries: {},
	colors: {},
	view: 'bar',
	total: {
		label: __('Total snippets', 'code-snippets'),
		count: Number(summary.active) + Number(summary.inactive)
	}
})

const getChartDefinitions = ({
	chartViews,
	setChartView,
	summary
}: InsightsChartDefinitionsProps): readonly InsightsChartDefinition[] => {
	const activationEntries = {
		active: { label: __('Active', 'code-snippets'), count: summary.active },
		inactive: { label: __('Inactive', 'code-snippets'), count: summary.inactive }
	}
	const locationEntries: Record<string, InsightsChartEntry> = Object.fromEntries(
		Object.entries(summary.locationCounts).map(([scope, count]) =>
			[scope, { label: SNIPPET_SCOPE_DESCRIPTIONS[scope as SnippetCodeScope], count }])
	)
	return [
		getTotalChartDefinition(summary),
		{
			key: 'type',
			title: __('Snippet type', 'code-snippets'),
			entries: summary.typeCounts,
			colors: INSIGHTS_TYPE_COLORS,
			view: chartViews.type,
			setView: view => setChartView('type', view)
		},
		{
			key: 'activation',
			title: __('Activation status', 'code-snippets'),
			entries: activationEntries,
			colors: INSIGHTS_ACTIVATION_COLORS,
			view: chartViews.activation,
			setView: view => setChartView('activation', view)
		},
		{
			key: 'location',
			title: __('Location', 'code-snippets'),
			entries: locationEntries,
			colors: INSIGHTS_LOCATION_COLORS,
			view: chartViews.location,
			setView: view => setChartView('location', view)
		},
		{
			key: 'tags',
			title: __('Tags', 'code-snippets'),
			entries: summary.tagCounts,
			colors: {},
			view: 'bar'
		}
	]
}

export const InsightsDashboard: React.FC<InsightsDashboardProps> = ({ summary }) => {
	const { chartViews, setChartView } = useInsightsChartViews()
	const chartDefinitions = getChartDefinitions({ chartViews, setChartView, summary })

	return <>
		<div className="snippets-page-header">
			<h1>{__('Insights', 'code-snippets')}</h1>
		</div>

		<hr className="wp-header-end"></hr>

		<section className="insights-chart-grid">
			{chartDefinitions.map(({ colors, entries, key, setView, title, total, view }) =>
				<InsightsChart
					key={key}
					chart={key}
					title={title}
					entries={entries}
					view={view}
					setView={setView}
					colors={colors}
					total={total}
				/>) }
		</section>
	</>
}
