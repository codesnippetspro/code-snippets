import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { DemoStage } from './types'

/** How long each stage holds before the driver advances. */
const STAGE_DURATIONS: Partial<Record<DemoStage, number>> = {
	library: 2600,
	preview: 3200,
	download: 2400,
	synced: 3000
}

const DEFAULT_DURATION = 2000

/** Uniform pause between stages when the visitor has asked for reduced motion. */
const REDUCED_MOTION_DURATION = 250

const FOLLOW_UP: Partial<Record<DemoStage, DemoStage>> = {
	library: 'preview',
	preview: 'download',
	download: 'synced',
	synced: 'finished'
}

const prefersReducedMotion = (): boolean =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches

export interface CloudLibraryDemoState {
	stage: DemoStage
	hasStarted: boolean
	isFinished: boolean
	reducedMotion: boolean
	play: VoidFunction
	skip: VoidFunction
	replay: VoidFunction
}

export const useCloudLibraryDemo = (): CloudLibraryDemoState => {
	const reducedMotion = useMemo(prefersReducedMotion, [])

	const [stage, setStage] = useState<DemoStage>('idle')
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
		advance('library')
	}, [advance, clearTimers])

	const replay = useCallback(() => {
		clearTimers()
		setStage('idle')
		timers.current.push(window.setTimeout(() => advance('library'), REDUCED_MOTION_DURATION))
	}, [advance, clearTimers])

	const skip = useCallback(() => {
		clearTimers()
		setStage('finished')
	}, [clearTimers])

	return {
		stage,
		hasStarted: 'idle' !== stage,
		isFinished: 'finished' === stage,
		reducedMotion,
		play,
		skip,
		replay
	}
}
