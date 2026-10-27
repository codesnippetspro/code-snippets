import { useEffect, useMemo, useState } from 'react'
import { SEARCH_FIELDS, SEARCH_LOCAL_SOURCES, SEARCH_PRO_SOURCES, SEARCH_SOURCES } from '../types/Search'
import { REST_BASES } from '../utils/restAPI'
import { isCloudConnected, isLicensed, isNetworkAdmin } from '../utils/screen'
import { buildUrl } from '../utils/urls'
import { useRestAPI } from './useRestAPI'
import type { SearchField, SearchGroup, SearchMethod, SearchResponse, SearchSource } from '../types/Search'
import type { AxiosInstance } from 'axios'

export const SEARCH_MIN_LENGTH = 2

const DEBOUNCE_MS = 150

export interface UseUniversalSearchProps {
	query: string
	fields?: readonly SearchField[]
	perSource?: number
	/** Whether to search at all. A disabled search sends no requests and reports no results. */
	enabled?: boolean
	/** Whether long descriptions are shortened to an excerpt around the match. */
	excerpts?: boolean
	/** Sources to search, out of those available. Defaults to every available source. */
	sources?: readonly SearchSource[]
	/** How the cloud is searched. */
	method?: SearchMethod
	/** How long typing must pause, in milliseconds, before a search is sent. */
	delay?: number
}

export interface UniversalSearch {
	/** The trimmed query, as sent to the server. */
	term: string
	/** Whether the query is long enough to search for. */
	isSearchable: boolean
	/** Groups with at least one result, in display order. */
	groups: SearchGroup[]
	/** Sources still waiting on a response for the current query. */
	loadingSources: SearchSource[]
	/** Total number of results across every group. */
	total: number
	/** Whether the local sources could not be searched for the current query. */
	hasFailed: boolean
}

interface SearchState {
	requestKey: string
	loaded: SearchSource[]
	groups: Partial<Record<SearchSource, SearchGroup>>
	hasFailed: boolean
}

export const isLocalSearchSource = (source: SearchSource): boolean =>
	(<readonly SearchSource[]> SEARCH_LOCAL_SOURCES).includes(source)

/**
 * Whether a source can be searched on this site. The Cloud Library needs both a
 * Pro license and a connection to Code Snippets Cloud.
 */
export const isSearchSourceAvailable = (source: SearchSource): boolean =>
	!SEARCH_PRO_SOURCES.includes(source) || isLicensed() && isCloudConnected()

/**
 * Each batch is one request: the local sources answer together, while every
 * remote source is asked for separately so a slow one holds up only itself.
 */
const getSourceBatches = (sources: readonly SearchSource[]): SearchSource[][] =>
	[
		SEARCH_LOCAL_SOURCES.filter(source => sources.includes(source)),
		...SEARCH_SOURCES
			.filter(source => !isLocalSearchSource(source) && sources.includes(source))
			.map(source => [source])
	].filter(batch => 0 < batch.length && batch.every(isSearchSourceAvailable))

const isLocalBatch = (sources: SearchSource[]): boolean =>
	sources.some(isLocalSearchSource)

interface SearchRequest {
	axiosInstance: AxiosInstance
	sources: SearchSource[]
	term: string
	fieldList: string
	perSource: number | undefined
	excerpts: boolean
	method: SearchMethod
	signal: AbortSignal
}

/**
 * Ask the server for a batch of sources, resolving with whatever groups it found
 * for them, or with `undefined` when the request failed.
 */
const requestGroups = ({
	axiosInstance,
	sources,
	term,
	fieldList,
	perSource,
	excerpts,
	method,
	signal
}: SearchRequest): Promise<SearchGroup[] | undefined> =>
	axiosInstance
		.get<SearchResponse>(
			buildUrl(REST_BASES.search, {
				q: term,
				sources: sources.join(','),
				fields: fieldList,
				per_source: perSource,
				excerpts: excerpts ? undefined : 'false',
				method: 'term' === method ? undefined : method,
				network: isNetworkAdmin() || undefined
			}),
			{ signal }
		)
		.then(response => response.data.groups)
		.catch(() => undefined)

interface SearchStateProps extends Omit<SearchRequest, 'sources' | 'signal'> {
	requestKey: string
	sourceList: string
	delay: number
}

const useSearchState = ({
	axiosInstance, requestKey, sourceList, term, fieldList, perSource, excerpts, method, delay
}: SearchStateProps) => {
	const [state, setState] = useState<SearchState>({ requestKey: '', loaded: [], groups: {}, hasFailed: false })

	useEffect(() => {
		if (!requestKey) {
			return undefined
		}

		const controller = new AbortController()
		const { signal } = controller

		const timeout = setTimeout(() => {
			for (const batch of getSourceBatches(<SearchSource[]> sourceList.split(','))) {
				void requestGroups({ axiosInstance, sources: batch, term, fieldList, perSource, excerpts, method, signal }).then(found => {
					if (signal.aborted) {
						return
					}

					// A remote source that cannot be reached is left out rather than reported.
					const groups = Object.fromEntries(
						batch.map(source => [source, found?.find(group => group.source === source)])
					)

					setState(previous => {
						const isSameRequest = previous.requestKey === requestKey

						return {
							requestKey,
							loaded: isSameRequest ? [...previous.loaded, ...batch] : batch,
							groups: { ...previous.groups, ...groups },
							hasFailed: isSameRequest && previous.hasFailed || !found && isLocalBatch(batch)
						}
					})
				})
			}
		}, delay)

		return () => {
			clearTimeout(timeout)
			controller.abort()
		}
	}, [axiosInstance, requestKey, term, fieldList, perSource, excerpts, sourceList, method, delay])

	return state
}

export const useUniversalSearch = ({
	query,
	fields = SEARCH_FIELDS,
	perSource,
	enabled = true,
	excerpts = true,
	sources = SEARCH_SOURCES,
	method = 'term',
	delay = DEBOUNCE_MS
}: UseUniversalSearchProps): UniversalSearch => {
	const { axiosInstance } = useRestAPI()

	const term = query.trim()
	const fieldList = fields.join(',')
	const sourceList = sources.join(',')
	const isSearchable = SEARCH_MIN_LENGTH <= term.length && '' !== fieldList
	const requestKey = enabled && isSearchable
		? `${term}|${fieldList}|${perSource}|${excerpts}|${sourceList}|${method}`
		: ''

	const state = useSearchState({ axiosInstance, requestKey, sourceList, term, fieldList, perSource, excerpts, method, delay })

	return useMemo(() => {
		if (!requestKey) {
			return { term, isSearchable, groups: [], loadingSources: [], total: 0, hasFailed: false }
		}

		const requested = getSourceBatches(<SearchSource[]> sourceList.split(',')).flat()
		const loaded = state.requestKey === requestKey ? state.loaded : []
		const loadingSources = requested.filter(source => !loaded.includes(source))

		// Results of the previous query stay up for the local sources until the
		// new ones arrive, so the list does not blink empty between keystrokes.
		const isShown = (source: SearchSource) =>
			loaded.includes(source) || isLocalSearchSource(source)

		const groups = requested
			.filter(isShown)
			.map(source => state.groups[source])
			.filter((group): group is SearchGroup => group !== undefined)

		return {
			term,
			isSearchable,
			groups,
			loadingSources,
			total: groups.reduce((total, group) => total + group.total, 0),
			hasFailed: state.requestKey === requestKey && state.hasFailed
		}
	}, [state, requestKey, term, isSearchable, sourceList])
}
