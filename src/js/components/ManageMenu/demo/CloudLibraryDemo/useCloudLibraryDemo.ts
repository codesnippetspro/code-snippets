import { useDemoTimeline } from '../common/useDemoTimeline'
import type { DemoTimeline, DemoTimelineState } from '../common/useDemoTimeline'
import type { DemoStage } from './types'

/**
 * Each stage carries one step of commentary, so it is given long enough to
 * read the callout and take in what the spotlight is pointing at. The preview
 * step holds longest: there is code on screen to actually look at.
 */
const TIMELINE: DemoTimeline<DemoStage> = {
	idle: 'idle',
	first: 'library',
	finished: 'finished',
	followUp: {
		library: 'preview',
		preview: 'download',
		download: 'synced',
		synced: 'finished'
	},
	durations: {
		library: 4800,
		preview: 5400,
		download: 4200,
		synced: 4800
	},
	defaultDuration: 3000
}
export const useCloudLibraryDemo = (): DemoTimelineState<DemoStage> =>
	useDemoTimeline({ timeline: TIMELINE })
