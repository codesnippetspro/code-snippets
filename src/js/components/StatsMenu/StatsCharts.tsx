import { _n, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useMemo } from 'react'
import { StatsChartViewToggle } from './StatsChartViewToggle'
import type { StatsChartEntry, StatsChartKey, StatsChartViews, StatsConfigurableChartKey } from '../../types/Stats'

const PERCENTAGE_MAX = 100

const DEFAULT_COLOR = '#646970'

const TAG_CLOUD_MIN_FONT_SIZE = 0.875

const TAG_CLOUD_FONT_SIZE_RANGE = 0.625

const getPieBackground = (
	entries: Readonly<Record<string, StatsChartEntry>>,
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
	entries: Readonly<Record<string, StatsChartEntry>>
	colors?: Readonly<Record<string, string>>
}

const EntryLabel: React.FC<Pick<StatsChartEntry, 'label' | 'url'>> = ({ label, url }) =>
	url ? <a className="stats-chart-entry-link" href={url}>{label}</a> : <>{label}</>

const BarChart: React.FC<ChartProps> = ({ colors, entries }) => {
	const entryCounts = useMemo(() =>
		Object.values(entries)
			.map(entry => Number(entry.count)),
	[entries])

	return (
		<ul className="stats-bar-chart">
			{Object.entries(entries).map(([key, entry]) =>
				<li key={key}>
					<span><EntryLabel {...entry} /></span>
					<div className="stats-bar-track" aria-hidden="true">
						<div
							className="stats-bar-fill"
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
		<div className="stats-pie-chart-content">
			<div
				className={classnames('stats-pie-chart', { 'is-empty': 0 === totalCount })}
				aria-hidden="true"
				style={0 === totalCount ? undefined : { background: getPieBackground(entries, colors, totalCount) }}
			/>
			<ul className="stats-pie-chart-legend">
				{Object.entries(entries).map(([key, entry]) =>
					<li key={key}>
						<span>
							<i aria-hidden="true" style={{ backgroundColor: colors?.[key] ?? DEFAULT_COLOR }} />
							<EntryLabel {...entry} />
						</span>
						<strong>{entry.count}</strong>
					</li>)}
			</ul>
		</div>
	)
}

const TagCloud: React.FC<ChartProps> = ({ entries }) => {
	const largestCount = useMemo(() =>
		Math.max(1, ...Object.values(entries).map(entry => Number(entry.count))),
	[entries])

	return (
		<ul className="stats-tags-cloud">
			{Object.entries(entries).map(([key, entry]) =>
				<li
					key={key}
					style={{ fontSize: `${TAG_CLOUD_MIN_FONT_SIZE + Number(entry.count) / largestCount * TAG_CLOUD_FONT_SIZE_RANGE}rem` }}
				>
					<EntryLabel {...entry} />
					<span className="screen-reader-text">{sprintf(
						_n(' (%s snippet)', ' (%s snippets)', Number(entry.count), 'code-snippets'),
						entry.count
					)}</span>
				</li>)}
		</ul>
	)
}

export interface StatsChartProps<Chart extends StatsConfigurableChartKey> {
	chart: Chart
	entries: Readonly<Record<string, StatsChartEntry>>
	title: string
	view: StatsChartViews[Chart]
	setView?: (view: StatsChartViews[Chart]) => void
	colors?: Readonly<Record<string, string>>
	views: readonly StatsChartViews[Chart][]
}

export const StatsChart = <Chart extends StatsConfigurableChartKey,>({
	chart,
	colors,
	entries,
	setView,
	title,
	view,
	views
}: StatsChartProps<Chart>) =>
	<section
		className="stats-chart-card"
		data-stats-chart={chart}
		data-view={view}
		aria-labelledby={`stats-chart-${chart}-heading`}
	>
		<div className="stats-chart-card-header">
			<h2 id={`stats-chart-${chart}-heading`}>{title}</h2>
			{setView && <StatsChartViewToggle title={title} view={view} setView={setView} views={views} />}
		</div>
		{'bar' === view
			? <BarChart colors={colors} entries={entries} />
			: 'pie' === view
				? <PieChart colors={colors} entries={entries} />
				: <TagCloud entries={entries} />}
	</section>

export interface TotalsStatsChartProps extends StatsChartEntry {
	chart: StatsChartKey
}

export const TotalsStatsChart: React.FC<TotalsStatsChartProps> = ({ chart, count, label }) =>
	<section className="stats-chart-card" data-stats-chart={chart} aria-label={label}>
		<div className="stats-number-chart">
			<strong className="stats-number-chart-value">{count}</strong>
			<span className="stats-number-chart-label">{label}</span>
		</div>
	</section>
