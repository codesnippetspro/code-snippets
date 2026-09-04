import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import {
	DEMO_PROMPT,
	getDraftSnippets,
	getRefinedSnippets,
	getRefinementPrompt,
	getSiteName
} from './demoScript'
import type { DemoSnippet, DemoStage } from './types'

/** Milliseconds between characters while a prompt types itself in. */
const CHAR_INTERVAL = 45

/**
 * How long each stage holds before the driver advances. Stages that type text
 * derive their own duration from the length of that text instead.
 *
 * Several stages share one step of commentary, so these are paced against the
 * step rather than the stage: every step is given long enough to read its
 * callout and take in what changed on screen.
 */
const STAGE_DURATIONS: Partial<Record<DemoStage, number>> = {
	'prompt-sent': 1200,
	'planning': 3600,
	'plan-ready': 3800,
	'plan-accepted': 1200,
	'building': 3600,
	'result-ready': 3000,
	'refine-open': 1500,
	'applying': 2800,
	'saved': 2600
}

/** Uniform pause between stages when the visitor has asked for reduced motion. */
const REDUCED_MOTION_DURATION = 250

/** Pause after a prompt finishes typing, before it is sent. */
const TYPING_TAIL = 1600

/** Fallback hold for any stage without an explicit duration. */
const DEFAULT_DURATION = 1600

const prefersReducedMotion = (): boolean =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches

export interface AiAgentDemoState {
	stage: DemoStage
	typedPrompt: string
	typedRefinement: string
	snippets: DemoSnippet[]
	hasStarted: boolean
	isFinished: boolean
	reducedMotion: boolean
	siteName: string
	refinementPrompt: string
	play: VoidFunction
	skip: VoidFunction
	replay: VoidFunction
}

// eslint-disable-next-line max-lines-per-function -- the timeline driver and its teardown belong together.
export const useAiAgentDemo = (): AiAgentDemoState => {
	const reducedMotion = useMemo(prefersReducedMotion, [])
	const siteName = useMemo(getSiteName, [])
	const refinementPrompt = useMemo(() => getRefinementPrompt(siteName), [siteName])

	const [stage, setStage] = useState<DemoStage>('idle')
	const [typedPrompt, setTypedPrompt] = useState('')
	const [typedRefinement, setTypedRefinement] = useState('')
	const [snippets, setSnippets] = useState<DemoSnippet[]>([])

	const timers = useRef<number[]>([])

	const clearTimers = useCallback(() => {
		timers.current.forEach(window.clearTimeout)
		timers.current = []
	}, [])

	useEffect(() => () => {
		timers.current.forEach(window.clearTimeout)
	}, [])

	const finishImmediately = useCallback(() => {
		clearTimers()
		setTypedPrompt(DEMO_PROMPT)
		setTypedRefinement(refinementPrompt)
		setSnippets(getRefinedSnippets(siteName))
		setStage('finished')
	}, [clearTimers, refinementPrompt, siteName])

	// Nothing is written to the site: the walkthrough shows what the agent
	// would produce, and the snippets it names exist only on screen.
	const enter = useCallback((next: DemoStage) => {
		setStage(next)

		if ('result-ready' === next) {
			setSnippets(getDraftSnippets())
		}

		if ('saved' === next) {
			setSnippets(getRefinedSnippets(siteName))
		}
	}, [siteName])

	const schedule = useCallback((next: DemoStage, delay: number, run: (stage: DemoStage) => void) => {
		timers.current.push(window.setTimeout(() => run(next), reducedMotion ? REDUCED_MOTION_DURATION : delay))
	}, [reducedMotion])

	const advance = useCallback((next: DemoStage) => {
		enter(next)

		const followUp: Partial<Record<DemoStage, DemoStage>> = {
			'typing-prompt': 'prompt-sent',
			'prompt-sent': 'planning',
			'planning': 'plan-ready',
			'plan-ready': 'plan-accepted',
			'plan-accepted': 'building',
			'building': 'result-ready',
			'result-ready': 'refine-open',
			'refine-open': 'typing-refinement',
			'typing-refinement': 'applying',
			'applying': 'saved',
			'saved': 'finished'
		}

		const upcoming = followUp[next]

		if (!upcoming) {
			return
		}

		const typingDuration = (text: string) =>
			(reducedMotion ? 0 : text.length * CHAR_INTERVAL) + TYPING_TAIL

		const delay = 'typing-prompt' === next
			? typingDuration(DEMO_PROMPT)
			: 'typing-refinement' === next
				? typingDuration(refinementPrompt)
				: STAGE_DURATIONS[next] ?? DEFAULT_DURATION

		schedule(upcoming, delay, advance)
	}, [enter, reducedMotion, refinementPrompt, schedule])

	const reset = useCallback(() => {
		clearTimers()
		setTypedPrompt('')
		setTypedRefinement('')
		setSnippets([])
		setStage('idle')
	}, [clearTimers])

	const play = useCallback(() => {
		reset()
		timers.current.push(window.setTimeout(() => advance('typing-prompt'), 0))
	}, [advance, reset])

	const replay = useCallback(() => {
		reset()
		timers.current.push(window.setTimeout(() => advance('typing-prompt'), REDUCED_MOTION_DURATION))
	}, [advance, reset])

	// Typewriter for whichever prompt the current stage is composing.
	useEffect(() => {
		const target = 'typing-prompt' === stage
			? { text: DEMO_PROMPT, set: setTypedPrompt }
			: 'typing-refinement' === stage
				? { text: refinementPrompt, set: setTypedRefinement }
				: undefined

		if (!target) {
			return
		}

		if (reducedMotion) {
			target.set(target.text)
			return
		}

		let index = 0
		const interval = window.setInterval(() => {
			index += 1
			target.set(target.text.slice(0, index))

			if (index >= target.text.length) {
				window.clearInterval(interval)
			}
		}, CHAR_INTERVAL)

		return () => window.clearInterval(interval)
	}, [reducedMotion, refinementPrompt, stage])

	return {
		stage,
		typedPrompt,
		typedRefinement,
		snippets,
		hasStarted: 'idle' !== stage,
		isFinished: 'finished' === stage,
		reducedMotion,
		siteName,
		refinementPrompt,
		play,
		skip: finishImmediately,
		replay
	}
}
