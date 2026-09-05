import { useEffect, useState } from 'react'
import { useRestAPI } from './useRestAPI'
import type { DuplicateReport, DuplicateSearchResponse } from '../types/Feedback'

/** Shortest title worth looking for existing reports of. */
const MIN_SEARCH_LENGTH = 6

/** How long to wait after the last keystroke before searching. */
const SEARCH_DEBOUNCE_MS = 600

/**
 * Offer reports already filed about whatever is being described, so the same problem is
 * not reported twice. A cloud that cannot answer leaves the list empty rather than
 * interrupting the report being written.
 */
export const useDuplicateReports = (restUrl: string, title: string): DuplicateReport[] => {
	const { api } = useRestAPI()
	const [duplicates, setDuplicates] = useState<DuplicateReport[]>([])

	useEffect(() => {
		const query = title.trim()

		if (MIN_SEARCH_LENGTH > query.length) {
			setDuplicates([])
			return
		}

		const timer = setTimeout(() => {
			api.get<DuplicateSearchResponse>(`${restUrl}/search?q=${encodeURIComponent(query)}`)
				.then(data => setDuplicates(data.results))
				.catch(() => setDuplicates([]))
		}, SEARCH_DEBOUNCE_MS)

		return () => clearTimeout(timer)
	}, [api, restUrl, title])

	return duplicates
}
