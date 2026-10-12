import { useCallback, useRef } from 'react'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { handleUnknownError } from '../../../utils/errors'
import { REST_BASES } from '../../../utils/restAPI'
import type { SubpageName } from '../../ManageMenu/subpages'

export type DemoName = 'ai-agent' | 'blueprints'

// Widened to any subpage so the toolbar can ask about a tab without first
// narrowing it: a tab with no recorded demo simply never appears in the list.
export const hasSeenDemo = (demo: SubpageName): boolean =>
	window.CODE_SNIPPETS?.demosSeen?.includes(demo) ?? false

/**
 * Records that a feature demo has been watched through to the end, so its
 * toolbar tab stops advertising itself as new.
 *
 * The walkthrough can be replayed, so the recording is made at most once per
 * page load and is a no-op when the demo has already been seen.
 */
export const useMarkDemoSeen = (demo: DemoName): VoidFunction => {
	const { api } = useRestAPI()
	const recorded = useRef(false)

	return useCallback(() => {
		if (recorded.current || hasSeenDemo(demo)) {
			return
		}

		recorded.current = true
		api.post<{ demos: DemoName[] }, { demo: DemoName }>(REST_BASES.preferences.demosSeen, { demo })
			.catch(handleUnknownError)
	}, [api, demo])
}
