import type { SnippetScope, SnippetType } from '../../../types/Snippet'

export type DemoLanguage = 'php' | 'javascript' | 'css' | 'html'

/**
 * Map a demo plan language to the snippet type used for badge colours and
 * Prism highlighting (the all-snippets page uses `js`, not `javascript`).
 */
export const languageToSnippetType = (language: DemoLanguage): SnippetType =>
	'javascript' === language ? 'js' : language

/**
 * The default snippet scope for each language, so saved snippets are stored as
 * the correct type (CSS/JS/HTML) rather than defaulting to a PHP scope.
 */
export const scopeForLanguage = (language: DemoLanguage): SnippetScope => {
	switch (language) {
		case 'css':
			return 'site-css'
		case 'javascript':
			return 'site-footer-js'
		case 'html':
			return 'content'
		default:
			return 'global'
	}
}

export interface DemoPlanPart {
	language: DemoLanguage
	name: string
	description: string
}

export interface DemoPlan {
	title: string
	summary: string
	parts: DemoPlanPart[]
}

export interface DemoSnippet {
	key: string
	name: string
	desc: string
	language: DemoLanguage
	code: string
}

/**
 * A snippet after it has been written to the site, pairing the scripted
 * content with the id the REST API assigned it.
 */
export interface SavedDemoSnippet extends DemoSnippet {
	id?: number
	error?: string
}

/**
 * Ordered stages of the scripted walkthrough. The driver only ever moves
 * forwards through this list, so a stage index doubles as a progress marker
 * for deciding which parts of the page have appeared yet.
 */
export const DEMO_STAGES = <const>[
	'idle',
	'typing-prompt',
	'prompt-sent',
	'planning',
	'plan-ready',
	'plan-accepted',
	'building',
	'result-ready',
	'refine-open',
	'typing-refinement',
	'applying',
	'saved',
	'finished'
]

export type DemoStage = typeof DEMO_STAGES[number]

export const stageIndex = (stage: DemoStage): number =>
	DEMO_STAGES.indexOf(stage)

/**
 * Whether the walkthrough has progressed at least as far as the given stage.
 */
export const hasReached = (current: DemoStage, stage: DemoStage): boolean =>
	stageIndex(current) >= stageIndex(stage)
