import { __ } from '@wordpress/i18n'
import type { CalloutContent } from '../../common/demo/DemoCallout'
import type { DemoStage } from './types'

/**
 * The five steps of the walkthrough, plus its closing note.
 *
 * Several stages share a step: the commentary describes the phase rather than
 * the individual stage, so it stays put while the thread moves underneath it.
 */
const STEPS: Record<string, CalloutContent> = {
	ask: {
		step: __('Step 1 of 5', 'code-snippets'),
		title: __('Ask in plain English', 'code-snippets'),
		body: __('No syntax to learn and no boilerplate to copy — describe what you want the site to do, and the agent works out what that means in code.', 'code-snippets')
	},
	plan: {
		step: __('Step 2 of 5', 'code-snippets'),
		title: __('Sent for planning', 'code-snippets'),
		body: __('Your request goes to Code Snippets Cloud, which works out what needs building — and whether that is one snippet or several — before writing anything.', 'code-snippets')
	},
	approve: {
		step: __('Step 3 of 5', 'code-snippets'),
		title: __('You approve the plan', 'code-snippets'),
		body: __('Nothing is written until you accept. Read what it intends to build, send it back for changes, or let it go ahead.', 'code-snippets')
	},
	build: {
		step: __('Step 4 of 5', 'code-snippets'),
		title: __('Built and added to your site', 'code-snippets'),
		body: __('Each part of the plan becomes a real snippet, written to suit your site rather than pulled from a template. They arrive inactive, so nothing runs until you have read the code and switched them on.', 'code-snippets')
	},
	refine: {
		step: __('Step 5 of 5', 'code-snippets'),
		title: __('Refine what it built', 'code-snippets'),
		body: __('Not quite right? Say what to change in plain English and it rewrites the snippets in place, keeping the conversation so each refinement builds on the last.', 'code-snippets')
	},
	done: {
		step: __('Done', 'code-snippets'),
		title: __('Ready to review', 'code-snippets'),
		body: __('The finished snippets are on your site, waiting for you to check them over and activate them.', 'code-snippets')
	}
}

const STAGE_STEPS: Partial<Record<DemoStage, keyof typeof STEPS>> = {
	'typing-prompt': 'ask',
	'prompt-sent': 'plan',
	'planning': 'plan',
	'plan-ready': 'approve',
	'plan-accepted': 'approve',
	'building': 'build',
	'result-ready': 'build',
	'refine-open': 'refine',
	'typing-refinement': 'refine',
	'applying': 'refine',
	'saved': 'done'
}

export const getCallout = (stage: DemoStage): CalloutContent | undefined => {
	const step = STAGE_STEPS[stage]
	return step ? STEPS[step] : undefined
}
