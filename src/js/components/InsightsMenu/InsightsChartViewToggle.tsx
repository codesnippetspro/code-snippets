import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { InsightsChartView } from '../../types/Insights'

interface ViewToggleButtonProps extends InsightsChartViewToggleProps {
	label: string
	currentView: InsightsChartView
}

const ViewToggleButton: React.FC<ViewToggleButtonProps> = ({ title, label, view, setView, currentView }) =>
	<button
		type="button"
		className={classnames('insights-chart-view-toggle-option', { 'active-view': currentView === view })}
		aria-pressed={currentView === view}
		title={title}
		onClick={() => setView(view)}
	>
		<span className={`dashicons dashicons-chart-${view}`} aria-hidden="true" />
		<span className="screen-reader-text">{label}</span>
	</button>

export interface InsightsChartViewToggleProps {
	title: string
	view: InsightsChartView
	setView: (view: InsightsChartView) => void
}

export const InsightsChartViewToggle: React.FC<InsightsChartViewToggleProps> = ({ title, view, setView }) =>
	<div
		className="insights-chart-view-toggle"
		role="group"
		aria-label={sprintf(__('%s chart view', 'code-snippets'), title)}
	>
		<ViewToggleButton
			view="pie"
			label={__('Pie chart view', 'code-snippets')}
			title={__('Switch to pie chart view', 'code-snippets')}
			setView={setView}
			currentView={view}
		/>

		<ViewToggleButton
			view="bar"
			label={__('Bar chart view', 'code-snippets')}
			title={__('Switch to bar chart view', 'code-snippets')}
			setView={setView}
			currentView={view}
		/>
	</div>
