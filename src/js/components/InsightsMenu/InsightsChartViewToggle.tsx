import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { InsightsChartView } from '../../types/Insights'

interface ViewToggleButtonProps<View extends InsightsChartView> extends InsightsChartViewToggleProps<View> {
	icon: string
	label: string
	currentView: View
}

const ViewToggleButton = <View extends InsightsChartView,>(
	{ icon, title, label, view, setView, currentView }: ViewToggleButtonProps<View>
) =>
	<button
		type="button"
		className={classnames('insights-chart-view-toggle-option', { 'active-view': currentView === view })}
		aria-pressed={currentView === view}
		title={title}
		onClick={() => setView(view)}
	>
		<span className={`dashicons dashicons-${icon}`} aria-hidden="true" />
		<span className="screen-reader-text">{label}</span>
	</button>

export interface InsightsChartViewToggleProps<View extends InsightsChartView> {
	title: string
	view: View
	setView: (view: View) => void
	views: readonly View[]
}

const VIEW_OPTIONS: Readonly<Record<InsightsChartView, { icon: string, label: string, title: string }>> = {
	pie: {
		icon: 'chart-pie',
		label: __('Chart view', 'code-snippets'),
		title: __('Switch to chart view', 'code-snippets')
	},
	bar: {
		icon: 'chart-bar',
		label: __('List view', 'code-snippets'),
		title: __('Switch to list view', 'code-snippets')
	},
	cloud: {
		icon: 'cloud',
		label: __('Tags cloud view', 'code-snippets'),
		title: __('Switch to tags cloud view', 'code-snippets')
	}
}

export const InsightsChartViewToggle = <View extends InsightsChartView,>(
	{ title, view, setView, views }: InsightsChartViewToggleProps<View>
) =>
	<div
		className="insights-chart-view-toggle"
		role="group"
		aria-label={sprintf(__('%s chart view', 'code-snippets'), title)}
	>
		{views.map(option => <ViewToggleButton
			key={option}
			view={option}
			{...VIEW_OPTIONS[option]}
			setView={setView}
			currentView={view}
		/>)}
	</div>
