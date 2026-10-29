import { useCallback, useEffect, useMemo, useState } from 'react'
import { useDemoTimeline } from '../common/useDemoTimeline'
import { DEMO_PROMPT, getDraftSnippets, getRefinedSnippets, getRefinementPrompt, getSiteName } from './demoScript'
import type { Dispatch, SetStateAction} from 'react'
import type { DemoTimeline, DemoTimelineState } from '../common/useDemoTimeline'
import type { DemoSnippet, DemoStage } from './types'

/** Milliseconds between characters while a prompt types itself in. */
const CHAR_INTERVAL = 45

const TIMELINE: DemoTimeline<DemoStage> = {
	idle: 'idle',
	first: 'typing-prompt',
	finished: 'finished',
	followUp: {
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
	},
	/**
	 * How long each stage holds before the driver advances. Stages that type text
	 * derive their own duration from the length of that text instead.
	 *
	 * Several stages share one step of commentary, so these are paced against the
	 * step rather than the stage: every step is given long enough to read its
	 * callout and take in what changed on screen.
	 */
	durations: {
		'prompt-sent': 1200,
		'planning': 3600,
		'plan-ready': 3800,
		'plan-accepted': 1200,
		'building': 3600,
		'result-ready': 3000,
		'refine-open': 1500,
		'applying': 2800,
		'saved': 2600
	},
	/** Fallback hold for any stage without an explicit duration. */
	defaultDuration: 1600
}

/** Pause after a prompt finishes typing, before it is sent. */
const TYPING_TAIL = 1600

const typingDuration = (text: string): number =>
	text.length * CHAR_INTERVAL + TYPING_TAIL

const INITIAL_PROMPTS: Partial<Record<DemoStage, string>> = {
	'typing-prompt': '',
	'typing-refinement': ''
}

const useFullPrompts = (siteName: string): typeof INITIAL_PROMPTS =>
	useMemo(() => {
		const refinementPrompt = getRefinementPrompt(siteName)

		return {
			'typing-prompt': DEMO_PROMPT,
			'typing-refinement': refinementPrompt
		}
	}, [siteName])

interface TypewriterProps {
	stage: DemoStage
	reducedMotion: boolean
	completePrompts: Partial<Record<DemoStage, string>>
	setTypedPrompts: Dispatch<SetStateAction<Partial<Record<DemoStage, string>>>>
}

const useTypewriter = ({ stage, reducedMotion, completePrompts, setTypedPrompts }: TypewriterProps) => {
	useEffect(() => {
		const completePrompt = completePrompts[stage]

		if (!completePrompt || reducedMotion) {
			setTypedPrompts(previous => ({ ...previous, [stage]: completePrompt }))
			return
		}

		let index = 0
		const interval = window.setInterval(() => {
			index += 1
			setTypedPrompts(previous => ({ ...previous, [stage]: completePrompt.slice(0, index) }))

			if (index >= completePrompt.length) {
				window.clearInterval(interval)
			}
		}, CHAR_INTERVAL)

		return () => window.clearInterval(interval)
	}, [stage, reducedMotion, completePrompts, setTypedPrompts])
}

export interface AiAgentDemoState extends DemoTimelineState<DemoStage> {
	typedPrompt: string
	typedRefinement: string
	snippets: DemoSnippet[]
}

export const useAiAgentDemo = (): AiAgentDemoState => {
	const siteName = useMemo(getSiteName, [])
	const completePrompts = useFullPrompts(siteName)
	const [typedPrompts, setTypedPrompts] = useState(INITIAL_PROMPTS)
	const [snippets, setSnippets] = useState<DemoSnippet[]>([])

	const getDynamicDuration = useCallback((nextStage: DemoStage): number | undefined => {
		const fullPrompt = completePrompts[nextStage]
		return fullPrompt ? typingDuration(fullPrompt) : undefined
	}, [completePrompts])

	const handleAdvance = useCallback((nextStage: DemoStage) => {
		switch (nextStage) {
			case 'result-ready':
				setSnippets(getDraftSnippets())
				break

			case 'saved':
				setSnippets(getRefinedSnippets(siteName))
				break
		}
	}, [siteName])

	const handleFinish = useCallback(() => {
		setTypedPrompts(completePrompts)
		setSnippets(getRefinedSnippets(siteName))
	}, [siteName, completePrompts])

	const handleReset = useCallback(() => {
		setTypedPrompts(INITIAL_PROMPTS)
		setSnippets([])
	}, [setTypedPrompts])

	const { stage, reducedMotion, ...timeline } = useDemoTimeline({
		timeline: TIMELINE,
		getDynamicDuration,
		onAdvance: handleAdvance,
		onFinish: handleFinish,
		onReset: handleReset
	})

	useTypewriter({ stage, reducedMotion, completePrompts, setTypedPrompts })

	return {
		stage,
		reducedMotion,
		snippets,
		typedPrompt: typedPrompts['typing-prompt'] ?? '',
		typedRefinement: typedPrompts['typing-refinement'] ?? '',
		...timeline
	}
}
