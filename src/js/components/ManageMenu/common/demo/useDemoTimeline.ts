import { useCallback, useMemo, useState } from 'react'
import type { DemoTimersState } from './useDemoTimers'

/** Uniform pause between stages when the visitor has asked for reduced motion. */
export const REDUCED_MOTION_DURATION = 250

export interface ReducedMotionState {
	reducedMotion: boolean
}

export const useReducedMotion = (): ReducedMotionState =>
	useMemo(() => ({
		reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
	}), [])

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

export interface DemoTimelineProps<S extends string> {
	timeline: DemoTimeline<S>
	timers: DemoTimersState
	onAdvance?: (nextStage: S, { reducedMotion }: { reducedMotion: boolean }) => void
	onFinish?: VoidFunction
	onReset?: VoidFunction
	getDynamicDuration?: (nextStage: S) => number | undefined
}

/**
 * Drives a scripted walkthrough forwards through its stages on a timer.
 *
 * The timeline is read once per render and is expected to be a module-level
 * constant, so the driver's callbacks stay stable for the life of the page.
 */
export const useDemoTimeline = <S extends string>({
	timeline: { idle, first, finished, followUp, durations, defaultDuration },
	timers: { addTimer, clearTimers },
	onAdvance,
	onFinish,
	onReset,
	getDynamicDuration
}: DemoTimelineProps<S>): DemoTimelineState<S> => {
	const { reducedMotion } = useReducedMotion()
	const [stage, setStage] = useState<S>(idle)

	const advance = useCallback((next: S) => {
		setStage(next)
		onAdvance?.(next, { reducedMotion })

		const upcoming = followUp[next]

		if (upcoming) {
			const delay = reducedMotion
				? REDUCED_MOTION_DURATION
				: getDynamicDuration?.(upcoming) ?? durations[upcoming] ?? defaultDuration

			addTimer(() => advance(upcoming), delay)
		}
	}, [defaultDuration, durations, followUp, reducedMotion, addTimer, onAdvance, getDynamicDuration])

	const play = useCallback(() => {
		clearTimers()
		addTimer(() => advance(first), 0)
	}, [advance, addTimer, clearTimers, first])

	const reset = useCallback(() => {
		clearTimers()
		onReset?.()
		setStage(idle)
	}, [clearTimers, onReset, setStage, idle])

	const replay = useCallback(() => {
		reset()
		addTimer(() => advance(first), REDUCED_MOTION_DURATION)
	}, [advance, reset, first, addTimer])

	const skip = useCallback(() => {
		clearTimers()
		onFinish?.()
		setStage(finished)
	}, [clearTimers, finished, onFinish])

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
