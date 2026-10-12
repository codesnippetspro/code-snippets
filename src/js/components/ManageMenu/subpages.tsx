import { AiAgentDemo } from './AiAgentDemo/AiAgentDemo'
import { BlueprintsDemo } from './BlueprintsDemo/BlueprintsDemo'
import { CloudLibraryDemo } from './CloudLibraryDemo/CloudLibraryDemo'
import { CommunityCloud } from './CommunityCloud/CommunityCloud'
import { SnippetsTable } from './SnippetsTable'
import type React from 'react'

export const SUBPAGES = ['snippets', 'blueprints', 'cloud-community', 'cloud-library', 'ai-agent'] as const

export type SubpageName = typeof SUBPAGES[number]

export interface Subpage {
	Component: React.FC

	/**
	 * Marks a walkthrough tab. An 'announce' tab advertises itself as new until
	 * its walkthrough has been watched, which only a tab with a recorded demo
	 * can do; a 'quiet' tab is only ever labelled as a demo.
	 */
	demo?: 'announce' | 'quiet'

	/**
	 * Marks a premium tab, which is chipped as such without a licence.
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
}
