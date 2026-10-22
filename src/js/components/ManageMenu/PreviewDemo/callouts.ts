import { __ } from '@wordpress/i18n'
import type { CalloutContent } from '../../common/demo/DemoCallout'
import type { DemoStage } from './types'

const STEPS: Record<string, CalloutContent> = {
	intro: {
		step: __('Demo', 'code-snippets'),
		title: __('This is a demo', 'code-snippets'),
		body: __('Press Play demo to watch a draft snippet change the page beside this note. Nothing here runs on your site.', 'code-snippets')
	},
	open: {
		step: __('Step 1 of 4', 'code-snippets'),
		title: __('Preview on a real page', 'code-snippets'),
		body: __('Pick the front page, a post, a page or an admin screen, and the draft opens there in a new tab, running on your site before you save or activate it.', 'code-snippets')
	},
	edit: {
		step: __('Step 2 of 4', 'code-snippets'),
		title: __('Change the code', 'code-snippets'),
		body: __('Keep working in the editor. PHP, CSS, JavaScript and HTML snippets can all be previewed this way.', 'code-snippets')
	},
	update: {
		step: __('Step 3 of 4', 'code-snippets'),
		title: __('Watch the page update', 'code-snippets'),
		body: __('The preview refreshes with your latest draft. It is private to you: visitors keep seeing the live site until you save and activate the snippet.', 'code-snippets')
	},
	log: {
		step: __('Step 4 of 4', 'code-snippets'),
		title: __('Catch problems before visitors do', 'code-snippets'),
		body: __('JavaScript errors and warnings from the draft are listed on the page, and a draft that fails shows a clear error page instead of breaking your site.', 'code-snippets')
	}
}

const STAGE_STEPS: Partial<Record<DemoStage, keyof typeof STEPS>> = {
	idle: 'intro',
	opened: 'open',
	typing: 'edit',
	updating: 'update',
	updated: 'update',
	log: 'log'
}

export const getCallout = (stage: DemoStage): CalloutContent | undefined => {
	const step = STAGE_STEPS[stage]
	return step ? STEPS[step] : undefined
}
