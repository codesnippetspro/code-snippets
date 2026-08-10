import React, { useCallback, useMemo, useState } from 'react'
import { SNIPPET_TYPES } from '../../../types/Snippet'
import { createContextHook } from '../../../utils/bootstrap'
import { fetchQueryParam, updateQueryParams } from '../../../utils/urls'
import type { SnippetType } from '../../../types/Snippet'
import type { PropsWithChildren } from 'react'

export const SNIPPET_STATUSES = [
	'all',
	'active', 'inactive', 'recently_active',
	'locked', 'unlocked',
	'trashed'
] as const

export type SnippetStatus = typeof SNIPPET_STATUSES[number]
export const INDEX_STATUS: SnippetStatus = 'all'

const isSnippetStatus = (status: unknown): status is SnippetStatus =>
	SNIPPET_STATUSES.includes(status as SnippetStatus)

const isSnippetType = (type: unknown): type is SnippetType =>
	SNIPPET_TYPES.includes(type as SnippetType)

const parseSearchQuery = (query?: string): [string | undefined, number | undefined] => {
	const lineMatch = query?.trim().match(/@line:(?<line>\d+)/)
	const lineNumber = lineMatch?.groups?.line ? parseInt(lineMatch.groups.line, 10) : undefined

	return lineMatch && lineNumber
		? [query?.replace(lineMatch[0], '').trim(), lineNumber]
		: [query, undefined]
}

export interface SnippetsTableFiltersContext {
	currentTag: string | undefined
	currentType: SnippetType | undefined
	searchQuery: string | undefined
	currentStatus: SnippetStatus
	setCurrentTag: (tag?: string) => void
	setCurrentType: (type?: SnippetType) => void
	setSearchQuery: (query?: string) => void
	setCurrentStatus: (status: SnippetStatus) => void
	searchLineNumber?: number
	searchQueryText?: string
}

const [Context, useSnippetsFilters] = createContextHook<SnippetsTableFiltersContext>('useSnippetsFilters')

export const WithSnippetsTableFilters: React.FC<PropsWithChildren> = ({ children }) => {
	const [currentTag, setTag] = useState(() => fetchQueryParam('tag'))
	const [searchQuery, setSearch] = useState(() => fetchQueryParam('s'))

	const [currentType, setCurrentType] = useState(() => {
		const type = fetchQueryParam('type')
		return isSnippetType(type) ? type : undefined
	})

	const [currentStatus, setCurrentStatus] = useState(() => {
		const status = fetchQueryParam('status')
		return isSnippetStatus(status) ? status : INDEX_STATUS
	})

	const [searchQueryText, searchLineNumber] = useMemo(
		() => parseSearchQuery(searchQuery),
		[searchQuery])

	const value: SnippetsTableFiltersContext = {
		currentTag,
		currentType,
		searchQuery,
		currentStatus,
		searchQueryText,
		searchLineNumber,

		setCurrentType: useCallback(type => {
			setCurrentType(type)
			updateQueryParams({ type })
		}, []),

		setCurrentStatus: useCallback(status => {
			setCurrentStatus(status)
			updateQueryParams({ status: INDEX_STATUS === status ? '' : status })
		}, []),

		setCurrentTag: useCallback(tag => {
			setTag(tag)
			updateQueryParams({ tag })
		}, []),

		setSearchQuery: useCallback(query => {
			setSearch(query)
			updateQueryParams({ s: query })
		}, [])
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useSnippetsFilters }
