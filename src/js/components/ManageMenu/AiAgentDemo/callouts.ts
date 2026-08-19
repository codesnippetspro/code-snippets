import { __ } from '@wordpress/i18n'
import type { CalloutContent } from '../../common/demo/DemoCallout'
import type { DemoStage } from './types'

/**
 * What the walkthrough is doing at each stage, shown alongside the thread so
 * the visitor can follow the reasoning rather than just watching it happen.
 */
const CALLOUTS: Partial<Record<DemoStage, CalloutContent>> = {
	'typing-prompt': {
		step: __('Step 1 of 5', 'code-snippets'),
		title: __('Ask in plain English', 'code-snippets'),
		body: __('No syntax to learn — describe what you want the site to do and the agent works out the rest.', 'code-snippets')
	},
	'prompt-sent': {
		step: __('Step 2 of 5', 'code-snippets'),
		title: __('Sent for planning', 'code-snippets'),
		body: __('Your request goes to Code Snippets Cloud, which decides what needs building before writing anything.', 'code-snippets')
	},
	'planning': {
		step: __('Step 2 of 5', 'code-snippets'),
		title: __('Sent for planning', 'code-snippets'),
		body: __('Your request goes to Code Snippets Cloud, which decides what needs building before writing anything.', 'code-snippets')
	},
	'plan-ready': {
		step: __('Step 3 of 5', 'code-snippets'),
		title: __('You approve the plan', 'code-snippets'),
		body: __('Nothing is written until you accept. Review what it intends to build, or send it back for changes.', 'code-snippets')
	},
	'plan-accepted': {
		step: __('Step 3 of 5', 'code-snippets'),
		title: __('You approve the plan', 'code-snippets'),
		body: __('Nothing is written until you accept. Review what it intends to build, or send it back for changes.', 'code-snippets')
	},
	'building': {
		step: __('Step 4 of 5', 'code-snippets'),
		title: __('Building the snippets', 'code-snippets'),
		body: __('Each part of the plan becomes a real snippet, written to suit your site rather than a generic template.', 'code-snippets')
	},
	'result-ready': {
		step: __('Step 4 of 5', 'code-snippets'),
		title: __('Added to your site', 'code-snippets'),
		body: __('Snippets arrive inactive, so nothing runs until you have read the code and switched them on.', 'code-snippets')
	},
	'refine-open': {
		step: __('Step 5 of 5', 'code-snippets'),
		title: __('Refine what it built', 'code-snippets'),
		body: __('Not quite right? Say what to change in plain English and it rewrites the snippets in place.', 'code-snippets')
	},
	'typing-refinement': {
		step: __('Step 5 of 5', 'code-snippets'),
		title: __('Refine what it built', 'code-snippets'),
		body: __('Not quite right? Say what to change in plain English and it rewrites the snippets in place.', 'code-snippets')
	},
	'applying': {
		step: __('Step 5 of 5', 'code-snippets'),
		title: __('Applying your changes', 'code-snippets'),
		body: __('The agent keeps the conversation, so each refinement builds on everything it has already written.', 'code-snippets')
	},
	'saved': {
		step: __('Done', 'code-snippets'),
		title: __('Ready to review', 'code-snippets'),
		body: __('The finished snippets are on your site, waiting for you to check them over and activate them.', 'code-snippets')
	}
}

export const getCallout = (stage: DemoStage): CalloutContent | undefined =>
	CALLOUTS[stage]
