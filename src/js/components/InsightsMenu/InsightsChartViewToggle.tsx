import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { InsightsChartView } from '../../types/Insights'

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
		<button
			type="button"
			className={classnames('insights-chart-view-toggle-option', { 'active-view': 'pie' === view })}
			aria-pressed={'pie' === view}
			title={__('Switch to pie chart view', 'code-snippets')}
			onClick={() => setView('pie')}
		>
			<span className="dashicons dashicons-chart-pie" aria-hidden="true" />
			<span className="screen-reader-text">{__('Pie chart view', 'code-snippets')}</span>
		</button>

		<button
			type="button"
			className={classnames('insights-chart-view-toggle-option', { 'active-view': 'bar' === view })}
			aria-pressed={'bar' === view}
			title={__('Switch to bar chart view', 'code-snippets')}
			onClick={() => setView('bar')}
		>
			<span className="dashicons dashicons-chart-bar" aria-hidden="true" />
			<span className="screen-reader-text">{__('Bar chart view', 'code-snippets')}</span>
		</button>
	</div>
