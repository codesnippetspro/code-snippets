import React, { useCallback, useMemo, useState } from 'react'
import { SNIPPET_STATUSES, SNIPPET_TYPES } from '../types/Snippet'
import { createContextHook } from '../utils/bootstrap'
import { parseSnippetObject } from '../utils/snippets/objects'
import { getSnippetType } from '../utils/snippets/snippets'
import { useSnippetsList } from './useSnippetsList'
import type { Snippet, SnippetStatus, SnippetType } from '../types/Snippet'
import type { PropsWithChildren } from 'react'

const fetchQueryParam = <T = string>(name: string, validValues?: readonly T[]): T | undefined => {
	const urlParams = new URLSearchParams(window.location.search)
	const value = urlParams.get(name)

	if (!value) {
		return undefined
	}

	if (validValues) {
		return validValues.includes(value as T) ? (value as T) : undefined
	}

	return value as T
}

const parseSearchQuery = (query?: string): [string | undefined, number | undefined] => {
	const lineMatch = query?.trim().match(/@line:(?<line>\d+)/)
	const lineNumber = lineMatch?.groups?.line ? parseInt(lineMatch.groups.line, 10) : undefined

	return lineMatch && lineNumber
		? [query?.replace(lineMatch[0], '').trim(), lineNumber]
		: [query, undefined]
}

const updateQueryParam = (name: string, value?: string) => {
	if ('URLSearchParams' in window) {
		const searchParams = new URLSearchParams(window.location.search)

		if (value) {
			searchParams.set(name, value)
		} else {
			searchParams.delete(name)
		}

		const newUrl = window.location.toString().replace(window.location.search, `?${searchParams.toString()}`)
		console.log(window.location.search, searchParams.toString(), newUrl)
		window.history.replaceState({}, document.title, newUrl)
	}
}

const useFilterSnippetsList = ({
	currentTag,
	currentType,
	searchQueryText,
	searchLineNumber
}: Pick<SnippetsTableContext, 'currentType' | 'currentTag' | 'searchLineNumber' | 'searchQueryText'>): Snippet[] => {
	const { snippetsList } = useSnippetsList()
	const sanitizedSearchQueryText = searchQueryText?.toLowerCase().trim()

	const snippets = snippetsList ?? window.CODE_SNIPPETS_MANAGE?.snippetsList.map(parseSnippetObject)

	return useMemo(
		() => snippets?.filter(snippet => {
			if (currentType && getSnippetType(snippet) !== currentType) {
				return false
			}

			if (currentTag && !snippet.tags.includes(currentTag)) {
				return false
			}

			if (sanitizedSearchQueryText) {
				if (searchLineNumber !== undefined) {
					const codeLines = snippet.code.split('\n')
					return codeLines[searchLineNumber]?.includes(sanitizedSearchQueryText)
				} else {
					const fields = ['name', 'desc', 'code', 'tags'] as const

					return fields.some(field =>
						('tags' === field ? snippet.tags.join(' ') : snippet[field])
							.toLowerCase().includes(sanitizedSearchQueryText.toLowerCase()))
				}
			}

			return true
		}) ?? [],
		[snippets, currentTag, currentType, sanitizedSearchQueryText, searchLineNumber])
}

const useSnippetsByStatus = (snippets: Snippet[]) =>
	useMemo(() =>
		snippets.reduce((acc, snippet) => {
			if (!acc.get(undefined)?.push(snippet)) {
				acc.set(undefined, [snippet])
			}

			const status = snippet.lastActive
				? 'recently_activated'
				: snippet.active ? 'active' : 'inactive'

			if (!acc.get(status)?.push(snippet)) {
				acc.set(status, [snippet])
			}

			return acc
		}, new Map<SnippetStatus | undefined, Snippet[]>()),
	[snippets])

export interface SnippetsTableContext {
	currentTag: string | undefined
	currentType: SnippetType | undefined
	searchQuery: string | undefined
	currentStatus: SnippetStatus | undefined
	setCurrentTag: (tag?: string) => void
	setCurrentType: (type?: SnippetType) => void
	setCurrentStatus: (status?: SnippetStatus) => void
	setSearchQuery: (query?: string) => void
	snippetsByStatus: Map<SnippetStatus | undefined, Snippet[]>
	searchLineNumber?: number
	searchQueryText?: string
}

export const [SnippetsTableContext, useSnippetsTable] = createContextHook<SnippetsTableContext>('useSnippetsTable')

export const WithSnippetsTableContext: React.FC<PropsWithChildren> = ({ children }) => {
	const [currentTag, setTag] = useState(() => fetchQueryParam('tag'))
	const [currentType, setType] = useState(() => fetchQueryParam('type', SNIPPET_TYPES))
	const [currentStatus, setStatus] = useState(() => fetchQueryParam('status', SNIPPET_STATUSES))
	const [searchQuery, setSearch] = useState(() => fetchQueryParam('s'))

	const setCurrentType = useCallback((type?: SnippetType) => {
		setType(type)
		updateQueryParam('type', type)
	}, [setType])

	const setCurrentStatus = useCallback((status?: SnippetStatus) => {
		setStatus(status)
		updateQueryParam('status', status)
	}, [setStatus])

	const setCurrentTag = useCallback((tag?: string) => {
		setTag(tag)
		updateQueryParam('tag', tag)
	}, [setTag])

	const setSearchQuery = useCallback((query?: string) => {
		setSearch(query)
		updateQueryParam('s', query)
	}, [setSearch])

	const [searchQueryText, searchLineNumber] = useMemo(() => parseSearchQuery(searchQuery), [searchQuery])

	const visibleSnippets = useFilterSnippetsList({ currentTag, currentType, searchLineNumber, searchQueryText })
	const snippetsByStatus = useSnippetsByStatus(visibleSnippets)

	const value: SnippetsTableContext = {
		currentTag,
		currentType,
		searchQuery,
		currentStatus,
		setCurrentTag,
		setCurrentType,
		setSearchQuery,
		setCurrentStatus,
		snippetsByStatus,
		searchLineNumber,
		searchQueryText
	}

	return <SnippetsTableContext.Provider value={value}>{children}</SnippetsTableContext.Provider>
}
