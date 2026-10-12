import { useEffect, useState } from 'react'
import { addQueryArg } from '../utils/restAPI'
import { TIMEOUTS } from '../../../tests/e2e/helpers/constants'
import { useRestAPI } from './useRestAPI'
import type { DuplicateReport, DuplicateSearchResponse } from '../types/Feedback'

/** Shortest title worth looking for existing reports of. */
const MIN_SEARCH_LENGTH = 6

/**
 * Offer reports already filed about whatever is being described, so the same problem is
 * not reported twice. A cloud that cannot answer leaves the list empty rather than
 * interrupting the report being written.
 */
export const useDuplicateReports = (searchUrl: string, title: string): DuplicateReport[] => {
	const { api } = useRestAPI()
	const [duplicates, setDuplicates] = useState<DuplicateReport[]>([])

	useEffect(() => {
		const query = title.trim()

		if (MIN_SEARCH_LENGTH > query.length) {
			setDuplicates([])
			return
		}

		let active = true

		const timer = setTimeout(() => {
			api.get<DuplicateSearchResponse>(addQueryArg({ url: searchUrl, name: 'q', value: query }))
				.then(data => active && setDuplicates(data.results))
				.catch(() => active && setDuplicates([]))
		}, TIMEOUTS.VERY_SHORT)

		return () => {
			active = false
			clearTimeout(timer)
		}
	}, [api, searchUrl, title])

	return duplicates
}
