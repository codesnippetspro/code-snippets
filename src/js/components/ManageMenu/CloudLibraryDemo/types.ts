/**
 * Ordered stages of the scripted walkthrough. The driver only moves forwards
 * through this list, so a stage index doubles as a progress marker.
 */
export const DEMO_STAGES = <const>[
	'idle',
	'library',
	'preview',
	'download',
	'synced',
	'finished'
]

export type DemoStage = typeof DEMO_STAGES[number]

/**
 * Whether the walkthrough has progressed at least as far as the given stage.
 */
export const hasReached = (current: DemoStage, stage: DemoStage): boolean =>
	DEMO_STAGES.indexOf(current) >= DEMO_STAGES.indexOf(stage)
