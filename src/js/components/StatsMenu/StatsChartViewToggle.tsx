import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { StatsChartView } from '../../types/Stats'

const VIEW_OPTIONS: Readonly<Record<StatsChartView, { icon: string, label: string, title: string }>> = {
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

interface ViewToggleButtonProps<View extends StatsChartView> {
	view: View
	icon: string
	label: string
	title: string
	setView: (view: View) => void
	currentView: View
}

const ViewToggleButton = <View extends StatsChartView, >({
	icon, title, label, view, setView, currentView
}: ViewToggleButtonProps<View>) =>
	<button
		type="button"
		className={classnames('stats-chart-view-toggle-option', { 'active-view': currentView === view })}
		aria-pressed={currentView === view}
		title={title}
		onClick={() => setView(view)}
	>
		<span className={`dashicons dashicons-${icon}`} aria-hidden="true" />
		<span className="screen-reader-text">{label}</span>
	</button>

export interface StatsChartViewToggleProps<View extends StatsChartView> {
	view: View
	title: string
	views: readonly View[]
	setView: (view: View) => void
}

export const StatsChartViewToggle = <View extends StatsChartView, >({
	title, view, setView, views
}: StatsChartViewToggleProps<View>) =>
	<div
		className="stats-chart-view-toggle"
		role="group"
		aria-label={sprintf(__('%s chart view', 'code-snippets'), title)}
	>
		{views.map(option =>
			<ViewToggleButton<View>
				key={option}
				view={option}
				{...VIEW_OPTIONS[option]}
				setView={setView}
				currentView={view}
			/>)}
	</div>
