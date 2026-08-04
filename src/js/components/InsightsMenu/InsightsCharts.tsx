import classnames from 'classnames'
import React from 'react'
import { InsightsChartViewToggle } from './InsightsChartViewToggle'
import type { InsightsChartEntry, InsightsChartKey, InsightsChartView } from '../../types/Insights'

const PERCENTAGE_MAX = 100

export const INSIGHTS_TYPE_COLORS: Readonly<Record<string, string>> = {
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

export const INSIGHTS_LOCATION_COLORS: Readonly<Record<string, string>> = {
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

interface InsightsChartProps {
	chart: InsightsChartKey
	entries: Readonly<Record<string, InsightsChartEntry>>
	title: string
	view: InsightsChartView
	setView?: (view: InsightsChartView) => void
	colors: Readonly<Record<string, string>>
	total?: InsightsChartEntry
}

const getCount = (entry: InsightsChartEntry): number => Number(entry.count)

const getLargestCount = (entries: Readonly<Record<string, InsightsChartEntry>>): number =>
	Math.max(1, ...Object.values(entries).map(getCount))

const getColor = (colors: Readonly<Record<string, string>>, key: string): string =>
	colors[key] ?? '#646970'

const getTotalCount = (entries: Readonly<Record<string, InsightsChartEntry>>): number =>
	Object.values(entries).reduce((count, entry) => count + getCount(entry), 0)

const getPieBackground = (
	entries: Readonly<Record<string, InsightsChartEntry>>,
	colors: Readonly<Record<string, string>>,
	totalCount: number
): string => {
	if (0 === totalCount) {
		return '#e2e5e5'
	}

	let start = 0
	const segments = Object.entries(entries)
		.filter(([, entry]) => 0 < getCount(entry))
		.map(([key, entry]) => {
			const end = start + getCount(entry) / totalCount * PERCENTAGE_MAX
			const segment = `${getColor(colors, key)} ${start}% ${end}%`

			start = end
			return segment
		})

	return `conic-gradient(${segments.join(', ')})`
}

interface BarChartProps extends Pick<InsightsChartProps, 'colors' | 'entries'> {
	largestCount: number
}

const BarChart: React.FC<BarChartProps> = ({ colors, entries, largestCount }) => (
	<ul className="insights-bar-chart">
		{Object.entries(entries).map(([key, entry]) =>
			<li key={key}>
				<span>{entry.label}</span>
				<div className="insights-bar-track" aria-hidden="true">
					<div
						className="insights-bar-fill"
						style={{
							backgroundColor: getColor(colors, key),
							inlineSize: `${getCount(entry) / largestCount * PERCENTAGE_MAX}%`
						}}
					/>
				</div>
				<strong>{entry.count}</strong>
			</li>)}
	</ul>
)

interface PieChartProps extends Pick<InsightsChartProps, 'colors' | 'entries'> {
	totalCount: number
}

const PieChart: React.FC<PieChartProps> = ({ colors, entries, totalCount }) => {
	const isEmpty = 0 === totalCount

	return <div className="insights-pie-chart-content">
		<div
			className={classnames('insights-pie-chart', { 'is-empty': isEmpty })}
			aria-hidden="true"
			style={isEmpty ? undefined : { background: getPieBackground(entries, colors, totalCount) }}
		/>
		<ul className="insights-pie-chart-legend">
			{Object.entries(entries).map(([key, entry]) =>
				<li key={key}>
					<span>
						<i aria-hidden="true" style={{ backgroundColor: getColor(colors, key) }} />
						{entry.label}
					</span>
					<strong>{entry.count}</strong>
				</li>)}
		</ul>
	</div>
}

export const InsightsChart: React.FC<InsightsChartProps> = ({
	chart,
	colors,
	entries,
	setView,
	title,
	total,
	view
}) => {
	const largestCount = getLargestCount(entries)
	const totalCount = getTotalCount(entries)

	return <section className="insights-chart-card" data-insights-chart={chart} data-view={total ? undefined : view}>
		{total
			? <div className="insights-number-chart">
				<strong className="insights-number-chart-value">{total.count}</strong>
				<span className="insights-number-chart-label">{total.label}</span>
			</div>
			: <>
				<div className="insights-chart-card-header">
					<h2>{title}</h2>
					{setView && <InsightsChartViewToggle title={title} view={view} setView={setView} />}
				</div>
				{'bar' === view
					? <BarChart colors={colors} entries={entries} largestCount={largestCount} />
					: <PieChart colors={colors} entries={entries} totalCount={totalCount} />}
			</>}
	</section>
}
