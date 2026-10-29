import { useCallback, useEffect, useMemo, useRef, useState } from 'react'

/** Uniform pause between stages when the visitor has asked for reduced motion. */
export const REDUCED_MOTION_DURATION = 250

export const prefersReducedMotion = (): boolean =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches

export interface DemoTimeline<S extends string> {
	/** The stage the walkthrough rests in before it is played. */
	idle: S
	/** The first stage entered when the walkthrough is played. */
	first: S
	/** The stage the walkthrough ends in, which skipping jumps straight to. */
	finished: S
	/** The stage that follows each one; a stage with none is where playback stops. */
	followUp: Partial<Record<S, S>>
	/** How long each stage holds before the driver advances. */
	durations: Partial<Record<S, number>>
	defaultDuration: number
}

export interface DemoTimelineState<S extends string> {
	stage: S
	hasStarted: boolean
	isFinished: boolean
	reducedMotion: boolean
	play: VoidFunction
	skip: VoidFunction
	replay: VoidFunction
}

/**
 * Drives a scripted walkthrough forwards through its stages on a timer.
 *
 * The timeline is read once per render and is expected to be a module-level
 * constant, so the driver's callbacks stay stable for the life of the page.
 */
export const useDemoTimeline = <S extends string>(timeline: DemoTimeline<S>): DemoTimelineState<S> => {
	const { idle, first, finished, followUp, durations, defaultDuration } = timeline
	const reducedMotion = useMemo(prefersReducedMotion, [])

	const [stage, setStage] = useState<S>(idle)
	const timers = useRef<number[]>([])

	const clearTimers = useCallback(() => {
		timers.current.forEach(window.clearTimeout)
		timers.current = []
	}, [])

	useEffect(() => () => {
		timers.current.forEach(window.clearTimeout)
	}, [])

	const advance = useCallback((next: S) => {
		setStage(next)

		const upcoming = followUp[next]

		if (!upcoming) {
			return
		}

		const delay = reducedMotion
			? REDUCED_MOTION_DURATION
			: durations[next] ?? defaultDuration

		timers.current.push(window.setTimeout(() => advance(upcoming), delay))
	}, [defaultDuration, durations, followUp, reducedMotion])

	const play = useCallback(() => {
		clearTimers()
		advance(first)
	}, [advance, clearTimers, first])

	const replay = useCallback(() => {
		clearTimers()
		setStage(idle)
		timers.current.push(window.setTimeout(() => advance(first), REDUCED_MOTION_DURATION))
	}, [advance, clearTimers, first, idle])

	const skip = useCallback(() => {
		clearTimers()
		setStage(finished)
	}, [clearTimers, finished])

	return {
		stage,
		hasStarted: idle !== stage,
		isFinished: finished === stage,
		reducedMotion,
		play,
		skip,
		replay
	}
}
