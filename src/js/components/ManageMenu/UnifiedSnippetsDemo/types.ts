import type { SnippetType } from '../../../types/Snippet'

export type DemoCodeType = 'php' | 'css' | 'js' | 'html' | 'config' | 'mixed'

export type DemoSourceType =
	| 'child-theme' | 'theme' | 'plugin' | 'builder' | 'database' | 'mu-plugin' | 'customizer' | 'server'

/** One piece of example code the scripted scan "finds" outside Code Snippets. */
export interface DemoFoundItem {
	key: string
	name: string
	code: string
	type: DemoCodeType
	sourceType: DemoSourceType
	sourceName: string
	sourcePath: string
	/** Whether the scan saw the original running where it was found. */
	isRunning: boolean
	/** Why the item cannot become a snippet; empty for items that can. */
	viewOnlyReason: string
	/** The ID the imported snippet is shown with once the walkthrough imports it. */
	importedId?: number
}

export const isImportable = (item: DemoFoundItem): boolean =>
	'' === item.viewOnlyReason

/** The preview modal highlights by snippet type, so anything that is not one reads as PHP. */
export const previewTypeFor = (type: DemoCodeType): SnippetType => {
	switch (type) {
		case 'css':
		case 'js':
		case 'html':
			return type

		default:
			return 'php'
	}
}

/**
 * Ordered stages of the scripted walkthrough. The driver only moves forwards
 * through this list, so a stage index doubles as a progress marker.
 */
export const DEMO_STAGES = <const>[
	'idle',
	'scan-start',
	'scan-themes',
	'scan-customizer',
	'scan-plugins',
	'scan-content',
	'results',
	'sources',
	'select',
	'importing',
	'imported',
	'view-only',
	'finished'
]

export type DemoStage = typeof DEMO_STAGES[number]

/**
 * Whether the walkthrough has progressed at least as far as the given stage.
 */
export const hasReached = (current: DemoStage, stage: DemoStage): boolean =>
	DEMO_STAGES.indexOf(current) >= DEMO_STAGES.indexOf(stage)
