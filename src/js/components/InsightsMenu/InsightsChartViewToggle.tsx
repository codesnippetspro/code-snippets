import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { InsightsChartView } from '../../types/Insights'

interface ViewToggleButtonProps extends InsightsChartViewToggleProps {
	icon: string
	label: string
	currentView: InsightsChartView
}

const ViewToggleButton: React.FC<ViewToggleButtonProps> = ({ icon, title, label, view, setView, currentView }) =>
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

export interface InsightsChartViewToggleProps {
	title: string
	view: InsightsChartView
	setView: (view: InsightsChartView) => void
	views?: readonly InsightsChartView[]
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

export const InsightsChartViewToggle: React.FC<InsightsChartViewToggleProps> = ({ title, view, setView, views = ['pie', 'bar'] }) =>
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
