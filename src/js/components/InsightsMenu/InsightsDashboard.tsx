import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { REST_BASES } from '../../utils/restAPI'
import { SNIPPET_SCOPE_DESCRIPTIONS } from '../../utils/snippets/snippets'
import { buildUrl } from '../../utils/urls'
import { useRestAPI } from '../../hooks/useRestAPI'
import { InsightsChart, TotalsInsightsChart } from './InsightsCharts'
import type { InsightChartPreferencesSchema, InsightsChartEntry, InsightsChartView, InsightsChartViews, InsightsConfigurableChartKey, InsightsSummary } from '../../types/Insights'
import type { SnippetCodeScope, SnippetType } from '../../types/Snippet'

export const DEFAULT_INSIGHTS_CHART_VIEWS: InsightsChartViews = window.CODE_SNIPPETS?.insightsChartViews ?? {
	type: 'bar',
	activation: 'pie',
	location: 'bar'
}

export const INSIGHTS_TYPE_COLORS: Readonly<Record<SnippetType, string>> = {
	php: '#2271b1',
	html: '#cd4510',
	css: '#9b59b6',
	js: '#f7d67a',
	cond: '#22826f'
}

export const INSIGHTS_ACTIVATION_COLORS: Readonly<Record<string, string>> = {
	active: '#118822',
	inactive: '#cd4510'
}

export const INSIGHTS_LOCATION_COLORS: Readonly<Record<SnippetCodeScope, string>> = {
	'global': '#ff9800',
	'admin': '#03c7d2',
	'front-end': '#d46f4d',
	'single-use': '#00bcd4',
	'content': '#2271b1',
	'head-content': '#5865f2',
	'body-content': '#3b5998',
	'footer-content': '#9b59b6',
	'admin-css': '#22826f',
	'site-css': '#cd4510',
	'site-head-js': '#f7d67a',
	'site-footer-js': '#2b71a3'
}

interface StaticChartProps {
	summary: InsightsSummary
}

interface ConfigurableChartProps extends StaticChartProps {
	view: InsightsChartView
	setView: (view: InsightsChartView) => void
}

const SnippetTypeChart: React.FC<ConfigurableChartProps> = ({ summary, view, setView }) =>
	<InsightsChart
		chart="type"
		title={__('Snippet type', 'code-snippets')}
		entries={Object.fromEntries(
			Object.entries(summary.typeCounts).map(([type, entry]) =>
				[type, { ...entry, url: buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'snippets', type }) }])
		)}
		colors={INSIGHTS_TYPE_COLORS}
		view={view}
		setView={setView}
	/>

const ActivationStatusChart: React.FC<ConfigurableChartProps> = ({ summary, view, setView }) =>
	<InsightsChart
		chart="activation"
		title={__('Activation status', 'code-snippets')}
		entries={{
			active: {
				label: __('Active', 'code-snippets'),
				count: summary.active,
				url: buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'snippets', status: 'active' })
			},
			inactive: {
				label: __('Inactive', 'code-snippets'),
				count: summary.inactive,
				url: buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'snippets', status: 'inactive' })
			}
		}}
		colors={INSIGHTS_ACTIVATION_COLORS}
		view={view}
		setView={setView}
	/>

const LocationChart = ({ summary, view, setView }: ConfigurableChartProps) => {
	const entries: Record<string, InsightsChartEntry> = Object.fromEntries(
		Object.entries(summary.locationCounts).map(([scope, count]) =>
			[scope, { label: SNIPPET_SCOPE_DESCRIPTIONS[scope as SnippetCodeScope], count }])
	)

	return (
		<InsightsChart
			chart="location"
			title={__('Location', 'code-snippets')}
			entries={entries}
			colors={INSIGHTS_LOCATION_COLORS}
			view={view}
			setView={setView}
		/>
	)
}

const TagsChart: React.FC<StaticChartProps> = ({ summary }) =>
	<InsightsChart
		chart="tags"
		title={__('Tags', 'code-snippets')}
		entries={Object.fromEntries(
			Object.entries(summary.tagCounts).map(([tag, entry]) =>
				[tag, { ...entry, url: buildUrl(window.CODE_SNIPPETS?.urls.manage, { tag }) }])
		)}
		view="bar"
	/>

export interface InsightsDashboardProps {
	summary: InsightsSummary
}

export const InsightsDashboard: React.FC<InsightsDashboardProps> = ({ summary }) => {
	const { api } = useRestAPI()
	const [chartViews, setChartViews] = useState<InsightsChartViews>(DEFAULT_INSIGHTS_CHART_VIEWS)

	const updateChartView = (chart: InsightsConfigurableChartKey, view: InsightsChartView) => {
		const views = { ...chartViews, [chart]: view }

		setChartViews(views)

		api.post<InsightChartPreferencesSchema, InsightChartPreferencesSchema>(REST_BASES.preferences.insights, { views })
			.catch((error: unknown) => {
				setChartViews(currentViews => currentViews === views ? chartViews : currentViews)
				console.error(error)
			})
	}

	return <>
		<div className="snippets-page-header">
			<h1>{__('Insights', 'code-snippets')}</h1>
		</div>

		<hr className="wp-header-end"></hr>

		<section className="insights-chart-grid">
			<TotalsInsightsChart
				chart="total"
				label={__('Total snippets', 'code-snippets')}
				count={Number(summary.active) + Number(summary.inactive)}
			/>

			<SnippetTypeChart
				summary={summary}
				view={chartViews.type}
				setView={view => updateChartView('type', view)}
			/>

			<ActivationStatusChart
				summary={summary}
				view={chartViews.activation}
				setView={view => updateChartView('activation', view)}
			/>

			<LocationChart
				summary={summary}
				view={chartViews.location}
				setView={view => updateChartView('location', view)}
			/>

			<TagsChart summary={summary} />
		</section>
	</>
}
