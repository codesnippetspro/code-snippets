import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { DEMO_SECTIONS } from './demoBlueprint'
import type { DemoStage } from './types'

/** How long each stage holds before the driver advances. */
const STAGE_DURATIONS: Partial<Record<DemoStage, number>> = {
	general: 1800,
	attributes: 1800,
	output: 2000,
	generating: 1200,
	generated: 600
}

const DEFAULT_DURATION = 1200

/** Uniform pause between stages when the visitor has asked for reduced motion. */
const REDUCED_MOTION_DURATION = 250

const FOLLOW_UP: Partial<Record<DemoStage, DemoStage>> = {
	general: 'attributes',
	attributes: 'output',
	output: 'generating',
	generating: 'generated',
	generated: 'finished'
}

const prefersReducedMotion = (): boolean =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches

export interface BlueprintsDemoState {
	stage: DemoStage
	activeSection: string
	/** Whether the visitor may pick sections themselves. */
	sectionsBrowsable: boolean
	hasStarted: boolean
	isFinished: boolean
	reducedMotion: boolean
	selectSection: (id: string) => void
	play: VoidFunction
	skip: VoidFunction
	replay: VoidFunction
}

// eslint-disable-next-line max-lines-per-function -- the timeline driver and its teardown belong together.
export const useBlueprintsDemo = (): BlueprintsDemoState => {
	const reducedMotion = useMemo(prefersReducedMotion, [])

	const [stage, setStage] = useState<DemoStage>('idle')
	const [activeSection, setActiveSection] = useState(DEMO_SECTIONS[0].id)

	const timers = useRef<number[]>([])

	const clearTimers = useCallback(() => {
		timers.current.forEach(window.clearTimeout)
		timers.current = []
	}, [])

	useEffect(() => () => {
		timers.current.forEach(window.clearTimeout)
	}, [])

	const advance = useCallback((next: DemoStage) => {
		setStage(next)

		// The first three stages are the section tabs themselves, so entering
		// one selects it.
		if (DEMO_SECTIONS.some(section => section.id === next)) {
			setActiveSection(next)
		}

		const upcoming = FOLLOW_UP[next]

		if (!upcoming) {
			return
		}

		const delay = reducedMotion
			? REDUCED_MOTION_DURATION
			: STAGE_DURATIONS[next] ?? DEFAULT_DURATION

		timers.current.push(window.setTimeout(() => advance(upcoming), delay))
	}, [reducedMotion])

	const play = useCallback(() => {
		clearTimers()
		advance('general')
	}, [advance, clearTimers])

	const replay = useCallback(() => {
		clearTimers()
		setStage('idle')
		setActiveSection(DEMO_SECTIONS[0].id)
		timers.current.push(window.setTimeout(() => advance('general'), REDUCED_MOTION_DURATION))
	}, [advance, clearTimers])

	const skip = useCallback(() => {
		clearTimers()
		setStage('finished')
		setActiveSection(DEMO_SECTIONS[DEMO_SECTIONS.length - 1].id)
	}, [clearTimers])

	const selectSection = useCallback((id: string) => {
		setActiveSection(id)
	}, [])

	return {
		stage,
		activeSection,
		sectionsBrowsable: 'finished' === stage,
		hasStarted: 'idle' !== stage,
		isFinished: 'finished' === stage,
		reducedMotion,
		selectSection,
		play,
		skip,
		replay
	}
}
