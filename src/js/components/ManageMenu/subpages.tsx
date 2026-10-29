import { AiAgentDemo } from './demo/AiAgentDemo/AiAgentDemo'
import { BlueprintsDemo } from './demo/BlueprintsDemo/BlueprintsDemo'
import { CloudLibraryDemo } from './demo/CloudLibraryDemo/CloudLibraryDemo'
import { CommunityCloud } from './cloud/CommunityCloud/CommunityCloud'
import { PreviewDemo } from './demo/PreviewDemo/PreviewDemo'
import { SearchResults } from './search/SearchResults/SearchResults'
import { SnippetsTable } from './SnippetsTable'
import { UnifiedSnippetsDemo } from './demo/UnifiedSnippetsDemo/UnifiedSnippetsDemo'
import type React from 'react'

export const SUBPAGES = [
	'snippets',
	'blueprints',
	'cloud-community',
	'cloud-library',
	'ai-agent',
	'unified-snippets',
	'preview',
	'search'
] as const

export type SubpageName = typeof SUBPAGES[number]

export interface Subpage {
	Component: React.FC

	/**
	 * Marks a walkthrough tab. An 'announce' tab advertises itself as new until
	 * its walkthrough has been watched, which only a tab with a recorded demo
	 * can do; a 'quiet' tab is only ever labeled as a demo.
	 */
	demo?: 'announce' | 'quiet'

	/**
	 * Marks a premium tab, which is chipped as such without a license.
	 */
	isPro?: boolean
}

/**
 * What each subpage of the manage screen renders, and how its toolbar tab
 * advertises itself.
 *
 * The toolbar and the page body read the same entry, so a subpage is described
 * once here rather than being kept in step across the two.
 */
export const SUBPAGE_ENTRIES: Record<SubpageName, Subpage> = {
	'snippets': { Component: SnippetsTable },
	'blueprints': { Component: BlueprintsDemo, demo: 'announce' },
	'cloud-community': { Component: CommunityCloud },
	'cloud-library': { Component: CloudLibraryDemo, demo: 'quiet' },
	'ai-agent': { Component: AiAgentDemo, demo: 'announce' },
	'unified-snippets': { Component: UnifiedSnippetsDemo, demo: 'announce' },
	'preview': { Component: PreviewDemo, demo: 'quiet' },
	'search': { Component: SearchResults },
}
