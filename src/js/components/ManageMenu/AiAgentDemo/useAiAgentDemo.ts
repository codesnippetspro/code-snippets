import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { unpackErrorResponse } from '../../../utils/errors'
import {
	DEMO_PROMPT,
	getDraftSnippets,
	getRefinedSnippets,
	getRefinementPrompt,
	getSiteName
} from './demoScript'
import { scopeForLanguage } from './types'
import type { DemoSnippet, DemoStage, SavedDemoSnippet } from './types'
import type { Snippet } from '../../../types/Snippet'

/** Milliseconds between characters while a prompt types itself in. */
const CHAR_INTERVAL = 32

/**
 * How long each stage holds before the driver advances. Stages that type text
 * derive their own duration from the length of that text instead.
 */
const STAGE_DURATIONS: Partial<Record<DemoStage, number>> = {
	'prompt-sent': 700,
	'planning': 2400,
	'plan-ready': 2200,
	'plan-accepted': 700,
	'building': 2600,
	'result-ready': 1800,
	'refine-open': 900,
	'applying': 2200,
	'saved': 1000
}

/** Uniform pause between stages when the visitor has asked for reduced motion. */
const REDUCED_MOTION_DURATION = 250

/** Pause after a prompt finishes typing, before it is sent. */
const TYPING_TAIL = 500

/** Fallback hold for any stage without an explicit duration. */
const DEFAULT_DURATION = 800

const prefersReducedMotion = (): boolean =>
	window.matchMedia('(prefers-reduced-motion: reduce)').matches

export interface AiAgentDemoState {
	stage: DemoStage
	typedPrompt: string
	typedRefinement: string
	snippets: SavedDemoSnippet[]
	saveError?: string
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
	const snippetsAPI = useSnippetsAPI()

	const reducedMotion = useMemo(prefersReducedMotion, [])
	const siteName = useMemo(getSiteName, [])
	const refinementPrompt = useMemo(() => getRefinementPrompt(siteName), [siteName])

	const [stage, setStage] = useState<DemoStage>('idle')
	const [typedPrompt, setTypedPrompt] = useState('')
	const [typedRefinement, setTypedRefinement] = useState('')
	const [snippets, setSnippets] = useState<SavedDemoSnippet[]>([])
	const [saveError, setSaveError] = useState<string>()

	// Ids assigned by the REST API on the first run, so replaying updates the
	// same two snippets instead of leaving a fresh pair behind every time.
	const savedIds = useRef<Record<string, number>>({})
	const timers = useRef<number[]>([])
	const mounted = useRef(true)

	// Writes are chained rather than fired in parallel: skipping the animation
	// can queue the refined snippets while the drafts are still being created,
	// and the refinement must not overtake — or duplicate — the create.
	const writes = useRef<Promise<unknown>>(Promise.resolve())

	const clearTimers = useCallback(() => {
		timers.current.forEach(window.clearTimeout)
		timers.current = []
	}, [])

	useEffect(() => {
		mounted.current = true

		return () => {
			mounted.current = false
			timers.current.forEach(window.clearTimeout)
		}
	}, [])

	const persist = useCallback((drafts: DemoSnippet[]) => {
		const write = (draft: DemoSnippet): Promise<SavedDemoSnippet> => {
			const fields: Partial<Snippet> = {
				name: draft.name,
				desc: draft.desc,
				code: draft.code,
				scope: scopeForLanguage(draft.language),
				active: false
			}

			const existingId = savedIds.current[draft.key]

			const request = existingId
				? snippetsAPI.update({ id: existingId, network: false, ...fields })
				: snippetsAPI.create(fields)

			return request
				.then(saved => {
					savedIds.current[draft.key] = saved.id
					return { ...draft, id: saved.id }
				})
				.catch((error: unknown) => ({ ...draft, error: unpackErrorResponse(error) }))
		}

		return Promise.all(drafts.map(write))
			.then(results => {
				if (!mounted.current) {
					return
				}

				setSnippets(results)

				const failure = results.find(result => result.error)?.error
				setSaveError(failure)
			})
	}, [snippetsAPI])

	const queueWrite = useCallback((drafts: DemoSnippet[]) => {
		writes.current = writes.current.then(() => persist(drafts))
	}, [persist])

	const finishImmediately = useCallback(() => {
		clearTimers()
		setTypedPrompt(DEMO_PROMPT)
		setTypedRefinement(refinementPrompt)
		setSnippets(getRefinedSnippets(siteName))
		setStage('finished')
		queueWrite(getRefinedSnippets(siteName))
	}, [clearTimers, queueWrite, refinementPrompt, siteName])

	const enter = useCallback((next: DemoStage) => {
		setStage(next)

		switch (next) {
			// The card claims the snippets are already on the site, so they are.
			case 'result-ready':
				setSnippets(getDraftSnippets())
				queueWrite(getDraftSnippets())
				break

			// The write runs alongside the "updating" animation so the refined
			// card usually has real snippet ids by the time it is revealed.
			case 'applying':
				queueWrite(getRefinedSnippets(siteName))
				break

			default:
				break
		}
	}, [queueWrite, siteName])

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
		setSaveError(undefined)
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
		saveError,
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
