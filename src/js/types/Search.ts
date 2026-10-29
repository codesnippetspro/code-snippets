import type { SubnavTab } from '../components/common/SubnavTabs'
import type { AI_SEARCH_TAB } from '../components/ManageMenu/common/UniversalSearch/SearchResultParts'
import type { CloudSnippetSchema } from './schema/CloudSnippetSchema'
import type { SnippetScope, SnippetType } from './Snippet'

export interface SearchTab extends SubnavTab<SearchSourceFilter> {
	locked: boolean
}

export const SEARCH_LOCAL_SOURCES = <const> ['snippets', 'settings', 'stats', 'welcome']

export const SEARCH_SOURCES = <const> [...SEARCH_LOCAL_SOURCES, 'docs', 'cloud', 'community']

export type SearchSource = typeof SEARCH_SOURCES[number]

export type SearchSourceFilter = 'all' | SearchSource | typeof AI_SEARCH_TAB

/** Sources that are only searched with a Pro license. */
export const SEARCH_PRO_SOURCES: readonly SearchSource[] = ['cloud']

/** How the cloud is searched: by keyword, or with AI, which matches by meaning. */
export type SearchMethod = 'term' | 'ai'

export const SEARCH_FIELDS = <const> ['title', 'description', 'code']

export type SearchField = typeof SEARCH_FIELDS[number]

export type SearchItemType = SnippetType | 'setting' | 'stat' | 'guide' | 'doc'

export interface SearchCodeExcerpt {
	readonly line: number
	readonly text: string
	/** The line above the match, or empty when the match is on the first line. */
	readonly before: string
	/** The line below the match, or empty when the match is on the last line. */
	readonly after: string
}

export interface SearchSnippetSummary {
	readonly id: number
	readonly scope: SnippetScope
	readonly active: boolean
	readonly locked: boolean
	readonly network: boolean
	readonly shared_network: boolean
}

export interface SearchItem {
	readonly id: string
	readonly source: SearchSource
	readonly type: SearchItemType
	readonly title: string
	readonly description: string
	readonly url: string
	readonly path: string
	readonly matched: SearchField[]
	readonly code?: SearchCodeExcerpt | null
	readonly snippet?: SearchSnippetSummary
	readonly cloud?: CloudSnippetSchema
}

export interface SearchGroup {
	readonly source: SearchSource
	readonly total: number
	readonly items: SearchItem[]
}

export interface SearchResponse {
	readonly query: string
	readonly groups: SearchGroup[]
}
