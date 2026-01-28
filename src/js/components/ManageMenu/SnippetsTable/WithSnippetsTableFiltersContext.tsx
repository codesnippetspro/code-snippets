import React, { useCallback, useMemo, useState } from 'react'
import { SNIPPET_STATUSES, SNIPPET_TYPES } from '../../../types/Snippet'
import { createContextHook } from '../../../utils/bootstrap'
import { fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import type { SnippetStatus, SnippetType } from '../../../types/Snippet'
import type { PropsWithChildren } from 'react'

const isSnippetType = (type: unknown): type is SnippetType =>
	SNIPPET_TYPES.includes(type as SnippetType)

const isSnippetStatus = (status: unknown): status is SnippetStatus =>
	SNIPPET_STATUSES.includes(status as SnippetStatus)

const parseSearchQuery = (query?: string): [string | undefined, number | undefined] => {
	const lineMatch = query?.trim().match(/@line:(?<line>\d+)/)
	const lineNumber = lineMatch?.groups?.line ? parseInt(lineMatch.groups.line, 10) : undefined

	return lineMatch && lineNumber
		? [query?.replace(lineMatch[0], '').trim(), lineNumber]
		: [query, undefined]
}

export interface SnippetsFiltersContext {
	currentTag: string | undefined
	currentType: SnippetType | undefined
	searchQuery: string | undefined
	currentStatus: SnippetStatus | undefined
	setCurrentTag: (tag?: string) => void
	setCurrentType: (type?: SnippetType) => void
	setSearchQuery: (query?: string) => void
	setCurrentStatus: (status?: SnippetStatus) => void
	searchLineNumber?: number
	searchQueryText?: string
}

export const [SnippetsFiltersContext, useSnippetsFilters] = createContextHook<SnippetsFiltersContext>('useSnippetsFilters')

export const WithSnippetsTableFiltersContext: React.FC<PropsWithChildren> = ({ children }) => {
	const [currentTag, setTag] = useState(() => fetchQueryParam('tag'))
	const [searchQuery, setSearch] = useState(() => fetchQueryParam('s'))

	const [currentType, setCurrentType] = useState(() => {
		const type = fetchQueryParam('type')
		return isSnippetType(type) ? type : undefined
	})

	const [currentStatus, setCurrentStatus] = useState(() => {
		const status = fetchQueryParam('status')
		return isSnippetStatus(status) ? status : undefined
	})

	const setters = {
		setCurrentType: useCallback((type?: SnippetType) => {
			setCurrentType(type)
			updateQueryParam('type', type)
		}, [setCurrentType]),
		setCurrentStatus: useCallback((status?: SnippetStatus) => {
			setCurrentStatus(status)
			updateQueryParam('status', status)
		}, [setCurrentStatus]),
		setCurrentTag: useCallback((tag?: string) => {
			setTag(tag)
			updateQueryParam('tag', tag)
		}, [setTag]),
		setSearchQuery: useCallback((query?: string) => {
			setSearch(query)
			updateQueryParam('s', query)
		}, [setSearch])
	}

	const [searchQueryText, searchLineNumber] = useMemo(
		() => parseSearchQuery(searchQuery),
		[searchQuery])

	const value: SnippetsFiltersContext = {
		currentTag,
		currentType,
		searchQuery,
		currentStatus,
		searchQueryText,
		searchLineNumber,
		...setters
	}

	return <SnippetsFiltersContext.Provider value={value}>{children}</SnippetsFiltersContext.Provider>
}
