import React, { useMemo } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { parseSnippetObject } from '../../../utils/snippets/objects'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { useSnippetsFilters } from './WithSnippetsTableFilters'
import type { PropsWithChildren } from 'react'
import type { Snippet, SnippetStatus } from '../../../types/Snippet'

const pushToMapArray = <K, V>(map: Map<K, V[]>, key: K, value: V) => {
	if (!map.get(key)?.push(value)) {
		map.set(key, [value])
	}
}

const getSnippetStatus = (snippet: Snippet): SnippetStatus => {
	if (snippet.trashed) {
		return 'trashed'
	}

	if (snippet.active) {
		return 'active'
	}

	if (snippet.lastActive) {
		return 'recently_activated'
	}

	return 'inactive'
}

const partitionSnippetsByStatus = (snippets: Snippet[]): Map<SnippetStatus | undefined, Snippet[]> =>
	snippets.reduce((acc, snippet) => {
		if (!snippet.trashed) {
			pushToMapArray(acc, undefined, snippet)
		}

		pushToMapArray(acc, getSnippetStatus(snippet), snippet)
		pushToMapArray(acc, snippet.locked ? 'locked' : 'unlocked', snippet)

		return acc
	}, new Map<SnippetStatus | undefined, Snippet[]>())

const partitionActiveSnippetsByCondition = (snippets: readonly Snippet[]): Map<Snippet['conditionId'], Snippet[]> =>
	snippets.reduce((acc, snippet) => {
		if (snippet.active) {
			pushToMapArray(acc, snippet.conditionId, snippet)
		}

		return acc
	}, new Map<Snippet['conditionId'], Snippet[]>())

export interface FilteredSnippetsContext {
	snippetsByStatus: Map<SnippetStatus | undefined, Snippet[]>
	activeByCondition: Map<Snippet['conditionId'], Snippet[]>
}

const [Context, useFilteredSnippets] = createContextHook<FilteredSnippetsContext>('useFilteredSnippets')

export const WithFilteredSnippetsContext: React.FC<PropsWithChildren> = ({ children }) => {
	const { snippetsList } = useSnippetsList()
	const { currentType, currentTag, searchLineNumber, searchQueryText } = useSnippetsFilters()

	const snippets = useMemo(
		() => snippetsList ?? window.CODE_SNIPPETS_MANAGE?.snippetsList.map(parseSnippetObject) ?? [],
		[snippetsList])

	const visibleSnippets = useMemo(() => {
		const searchFields = ['name', 'desc', 'code', 'tags'] as const
		const sanitizedSearchQueryText = searchQueryText?.toLowerCase().trim()

		return snippets.filter(snippet => {
			if (currentType && getSnippetType(snippet) !== currentType) {
				return false
			}

			if (currentTag && !snippet.tags.includes(currentTag)) {
				return false
			}

			if (sanitizedSearchQueryText) {
				return searchLineNumber !== undefined
					? snippet.code.split('\n')[searchLineNumber]?.includes(sanitizedSearchQueryText)
					: searchFields.some(field =>
						('tags' === field ? snippet.tags.join(' ') : snippet[field])
							.toLowerCase().includes(sanitizedSearchQueryText))
			}

			return true
		})
	}, [snippets, currentTag, currentType, searchQueryText, searchLineNumber])

	const snippetsByStatus = useMemo(
		() => partitionSnippetsByStatus(visibleSnippets),
		[visibleSnippets])

	const activeByCondition = useMemo(
		() => partitionActiveSnippetsByCondition(snippets),
		[snippets])

	const value: FilteredSnippetsContext = {
		snippetsByStatus,
		activeByCondition
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useFilteredSnippets }
