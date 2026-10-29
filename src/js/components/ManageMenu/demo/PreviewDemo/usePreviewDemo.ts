import { useEffect, useState } from 'react'
import { useDemoTimeline } from '../common/useDemoTimeline'
import { hasReached } from './types'
import type { DemoTimeline, DemoTimelineState } from '../common/useDemoTimeline'
import type { DemoStage } from './types'

/** The color the page loads with, before the draft is edited. */
export const SAVED_COLOR = '#dbeafe'

/** The color the walkthrough types into the draft. */
export const DRAFT_COLOR = '#fde68a'

/** Milliseconds between keystrokes while the color is retyped. */
const KEYSTROKE_INTERVAL = 140

/** Pause after the last keystroke, before the preview is refreshed. */
const TYPING_TAIL = 1600

/** The `#` is kept when the old value is deleted, so typing resumes after it. */
const HASH_LENGTH = 1

/**
 * Each keystroke of the edit: the saved value is deleted back to its `#`, then
 * the new one is typed in after it.
 */
const KEYSTROKES: readonly string[] = [
	...Array.from({ length: SAVED_COLOR.length }, (_, index) => SAVED_COLOR.slice(0, SAVED_COLOR.length - index)),
	...Array.from({ length: DRAFT_COLOR.length - HASH_LENGTH }, (_, index) =>
		DRAFT_COLOR.slice(0, HASH_LENGTH + index + 1))
]

const TIMELINE: DemoTimeline<DemoStage> = {
	idle: 'idle',
	first: 'opened',
	finished: 'finished',
	followUp: {
		opened: 'typing',
		typing: 'updating',
		updating: 'updated',
		updated: 'log',
		log: 'finished'
	},
	durations: {
		opened: 4600,
		typing: KEYSTROKES.length * KEYSTROKE_INTERVAL + TYPING_TAIL,
		updating: 1800,
		updated: 4600,
		log: 5400
	},
	defaultDuration: 3000
}

export interface PreviewDemoState extends DemoTimelineState<DemoStage> {
	/** The colour value as it currently reads in the code box. */
	typedColor: string
	/** The background the previewed page is showing. */
	pageColor: string
}

export const usePreviewDemo = (): PreviewDemoState => {
	const timeline = useDemoTimeline(TIMELINE)
	const { stage, reducedMotion } = timeline
	const [keystroke, setKeystroke] = useState(0)

	useEffect(() => {
		if ('typing' !== stage) {
			return
		}

		if (reducedMotion) {
			setKeystroke(KEYSTROKES.length - 1)
			return
		}

		let index = 0
		setKeystroke(index)

		const interval = window.setInterval(() => {
			index += 1
			setKeystroke(Math.min(index, KEYSTROKES.length - 1))

			if (index >= KEYSTROKES.length - 1) {
				window.clearInterval(interval)
			}
		}, KEYSTROKE_INTERVAL)

		return () => window.clearInterval(interval)
	}, [reducedMotion, stage])

	const editedColor = hasReached(stage, 'updating') ? DRAFT_COLOR : SAVED_COLOR

	return {
		...timeline,
		typedColor: 'typing' === stage ? KEYSTROKES[keystroke] : editedColor,
		pageColor: hasReached(stage, 'updated') ? DRAFT_COLOR : SAVED_COLOR
	}
}
