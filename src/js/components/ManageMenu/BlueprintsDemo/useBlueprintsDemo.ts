import { useCallback, useState } from 'react'
import { useDemoTimeline } from '../common/demo/useDemoTimeline'
import { useDemoTimers } from '../common/demo/useDemoTimers'
import { DEMO_SECTIONS } from './demoBlueprint'
import type { DemoTimeline, DemoTimelineState, ReducedMotionState } from '../common/demo/useDemoTimeline'
import type { DemoStage } from './types'

const TIMELINE: DemoTimeline<DemoStage> = {
	idle: 'idle',
	first: 'general',
	finished: 'finished',
	followUp: {
		general: 'attributes',
		attributes: 'output',
		output: 'generating',
		generating: 'generated',
		generated: 'finished'
	},
	/**
	 * How long each stage holds before the driver advances. Each stage carries one
	 * step of commentary, so it is given long enough to read the callout and take
	 * in the section that changed.
	 */
	durations: {
		general: 4500,
		attributes: 4500,
		output: 4800,
		generating: 3600,
		generated: 3000
	},
	defaultDuration: 3000
}

/** How long the panel spends faded out while its section is swapped. */
const FADE_DURATION = 220

export interface BlueprintsDemoState extends DemoTimelineState<DemoStage> {
	activeSection: string
	/** Whether the panel is mid-crossfade between two sections. */
	isFading: boolean
	/** Whether the visitor may pick sections themselves. */
	sectionsBrowsable: boolean
	selectSection: (id: string) => void
}

export const useBlueprintsDemo = (): BlueprintsDemoState => {
	const timers = useDemoTimers()
	const [activeSection, setActiveSection] = useState(DEMO_SECTIONS[0].id)
	const [isFading, setIsFading] = useState(false)

	// Swapping the panel's contents outright reads as a jump cut, so the panel
	// fades out, changes section, and fades back in.
	const crossfadeTo = useCallback((id: string, { reducedMotion }: ReducedMotionState) => {
		setActiveSection(previous => {
			if (previous !== id) {
				setIsFading(true)

				timers.addTimer(() => {
					setActiveSection(id)
					setIsFading(false)
				}, reducedMotion ? 0 : FADE_DURATION)
			}

			return previous
		})
	}, [timers])

	const handleAdvance = useCallback((next: DemoStage, { reducedMotion }: ReducedMotionState) => {
		// The first three stages are the section tabs themselves, so entering
		// one selects it.
		if (DEMO_SECTIONS.some(section => section.id === next)) {
			crossfadeTo(next, { reducedMotion })
		}
	}, [crossfadeTo])

	const handleReset = useCallback(() => {
		setIsFading(false)
		setActiveSection(DEMO_SECTIONS[0].id)
	}, [])

	const handleFinish = useCallback(() => {
		setIsFading(false)
		setActiveSection(DEMO_SECTIONS[DEMO_SECTIONS.length - 1].id)
	}, [])

	const { stage, reducedMotion, ...timeline } = useDemoTimeline({
		timers,
		timeline: TIMELINE,
		onAdvance: handleAdvance,
		onFinish: handleFinish,
		onReset: handleReset
	})

	const selectSection = useCallback((id: string) => {
		crossfadeTo(id, { reducedMotion })
	}, [crossfadeTo, reducedMotion])

	return {
		stage,
		activeSection,
		isFading,
		reducedMotion,
		sectionsBrowsable: TIMELINE.finished === stage,
		selectSection,
		...timeline
	}
}
