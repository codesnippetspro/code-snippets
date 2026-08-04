import { useCallback, useRef, useState } from 'react'
import { DEFAULT_INSIGHTS_CHART_VIEWS } from '../types/Insights'
import { handleUnknownError } from '../utils/errors'
import { REST_BASES } from '../utils/restAPI'
import { useRestAPI } from './useRestAPI'
import type { InsightsChartView, InsightsChartViews, InsightsConfigurableChartKey } from '../types/Insights'

export interface UseInsightsChartViews {
	chartViews: InsightsChartViews
	setChartView: (chart: InsightsConfigurableChartKey, view: InsightsChartView) => void
}

export const useInsightsChartViews = (): UseInsightsChartViews => {
	const { api } = useRestAPI()
	const [chartViews, setChartViews] = useState<InsightsChartViews>(
		() => window.CODE_SNIPPETS?.insightsChartViews ?? DEFAULT_INSIGHTS_CHART_VIEWS
	)
	const chartViewsRef = useRef(chartViews)

	const setChartView = useCallback((chart: InsightsConfigurableChartKey, view: InsightsChartView) => {
		const previousViews = chartViewsRef.current
		const views = { ...previousViews, [chart]: view }

		chartViewsRef.current = views
		setChartViews(views)
		api.post<{ views: InsightsChartViews }, { views: InsightsChartViews }>(
			`${REST_BASES.preferences}/insights-chart-views`,
			{ views }
		).catch((error: unknown) => {
			if (views === chartViewsRef.current) {
				chartViewsRef.current = previousViews
				setChartViews(previousViews)
			}

			handleUnknownError(error)
		})
	}, [api])

	return { chartViews, setChartView }
}
