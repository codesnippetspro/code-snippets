export type DemoFieldType = 'text' | 'textarea' | 'select'

export interface DemoField {
	name: string
	label: string
	type: DemoFieldType
	value: string
	required?: boolean
	description?: string
}

export interface DemoRepeaterRow {
	id: string
	values: string[]
}

export interface DemoRepeater {
	label: string
	addLabel: string
	columns: DemoField[]
	rows: DemoRepeaterRow[]
}

export interface DemoSection {
	id: string
	title: string
	description?: string
	fields: DemoField[]
	repeater?: DemoRepeater
}

/**
 * Ordered stages of the scripted walkthrough. The driver only moves forwards
 * through this list, so a stage index doubles as a progress marker.
 */
export const DEMO_STAGES = <const>[
	'idle',
	'general',
	'attributes',
	'output',
	'generating',
	'generated',
	'finished'
]

export type DemoStage = typeof DEMO_STAGES[number]

/**
 * Whether the walkthrough has progressed at least as far as the given stage.
 */
export const hasReached = (current: DemoStage, stage: DemoStage): boolean =>
	DEMO_STAGES.indexOf(current) >= DEMO_STAGES.indexOf(stage)
