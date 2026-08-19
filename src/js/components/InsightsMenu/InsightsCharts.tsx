import classnames from 'classnames'
import React, { useMemo } from 'react'
import { InsightsChartViewToggle } from './InsightsChartViewToggle'
import type { InsightsChartEntry, InsightsChartKey, InsightsChartView } from '../../types/Insights'

const PERCENTAGE_MAX = 100

const DEFAULT_COLOR = '#646970'

const getPieBackground = (
	entries: Readonly<Record<string, InsightsChartEntry>>,
	colors: Readonly<Record<string, string>> | undefined,
	totalCount: number
): string => {
	let start = 0
	const segments = Object.entries(entries)
		.filter(([, entry]) => 0 < Number(entry.count))
		.map(([key, entry]) => {
			const end = start + Number(entry.count) / totalCount * PERCENTAGE_MAX
			const segment = `${colors?.[key] ?? DEFAULT_COLOR} ${start}% ${end}%`

			start = end
			return segment
		})

	return `conic-gradient(${segments.join(', ')})`
}

interface ChartProps {
	entries: Readonly<Record<string, InsightsChartEntry>>
	colors?: Readonly<Record<string, string>>
}

const BarChart: React.FC<ChartProps> = ({ colors, entries }) => {
	const entryCounts = useMemo(() =>
		Object.values(entries)
			.map(entry => Number(entry.count)),
	[entries])

	return (
		<ul className="insights-bar-chart">
			{Object.entries(entries).map(([key, entry]) =>
				<li key={key}>
					<span>{entry.label}</span>
					<div className="insights-bar-track" aria-hidden="true">
						<div
							className="insights-bar-fill"
							style={{
								backgroundColor: colors?.[key] ?? DEFAULT_COLOR,
								inlineSize: `${Number(entry.count) / Math.max(1, ...entryCounts) * PERCENTAGE_MAX}%`
							}}
						/>
					</div>
					<strong>{entry.count}</strong>
				</li>)}
		</ul>
	)
}

const PieChart: React.FC<ChartProps> = ({ colors, entries }) => {
	const totalCount = useMemo(() =>
		Object.values(entries).reduce((count, entry) =>
			count + Number(entry.count), 0),
	[entries])

	return (
		<div className="insights-pie-chart-content">
			<div
				className={classnames('insights-pie-chart', { 'is-empty': 0 === totalCount })}
				aria-hidden="true"
				style={0 === totalCount ? undefined : { background: getPieBackground(entries, colors, totalCount) }}
			/>
			<ul className="insights-pie-chart-legend">
				{Object.entries(entries).map(([key, entry]) =>
					<li key={key}>
						<span>
							<i aria-hidden="true" style={{ backgroundColor: colors?.[key] ?? DEFAULT_COLOR }} />
							{entry.label}
						</span>
						<strong>{entry.count}</strong>
					</li>)}
			</ul>
		</div>
	)
}

export interface InsightsChartProps {
	chart: InsightsChartKey
	entries: Readonly<Record<string, InsightsChartEntry>>
	title: string
	view: InsightsChartView
	setView?: (view: InsightsChartView) => void
	colors?: Readonly<Record<string, string>>
}

export const InsightsChart: React.FC<InsightsChartProps> = ({
	chart,
	colors,
	entries,
	setView,
	title,
	view
}) =>
	<section className="insights-chart-card" data-insights-chart={chart} data-view={view}>
		<div className="insights-chart-card-header">
			<h2>{title}</h2>
			{setView && <InsightsChartViewToggle title={title} view={view} setView={setView} />}
		</div>
		{'bar' === view
			? <BarChart colors={colors} entries={entries} />
			: <PieChart colors={colors} entries={entries} />}
	</section>

export interface TotalsInsightsChartProps extends InsightsChartEntry {
	chart: InsightsChartKey
}

export const TotalsInsightsChart: React.FC<TotalsInsightsChartProps> = ({ chart, count, label }) =>
	<section className="insights-chart-card" data-insights-chart={chart}>
		<div className="insights-number-chart">
			<strong className="insights-number-chart-value">{count}</strong>
			<span className="insights-number-chart-label">{label}</span>
		</div>
	</section>
