import { __ } from '@wordpress/i18n'
import type { CalloutContent } from '../common/DemoCallout'
import type { DemoStage } from './types'

/**
 * The five steps of the walkthrough. The scan spans several stages that share
 * its step, so the commentary stays put while the progress line moves on.
 */
const STEPS: Record<string, CalloutContent> = {
	scan: {
		step: __('Step 1 of 5', 'code-snippets'),
		title: __('Find the code hiding on your site', 'code-snippets'),
		body: __('One scan looks through your themes, the Customizer, other plugins, page content and page builders. It only reads your site; nothing is changed.', 'code-snippets')
	},
	results: {
		step: __('Step 2 of 5', 'code-snippets'),
		title: __('Everything in one place', 'code-snippets'),
		body: __('What the scan found is counted by type, so you can see at a glance how much custom code lives outside Code Snippets.', 'code-snippets')
	},
	sources: {
		step: __('Step 3 of 5', 'code-snippets'),
		title: __('See where each item came from', 'code-snippets'),
		body: __('Every find shows the theme file, plugin setting, Customizer panel or page it lives in, so nothing is left a mystery.', 'code-snippets')
	},
	import: {
		step: __('Step 4 of 5', 'code-snippets'),
		title: __('Import one item or many', 'code-snippets'),
		body: __('Import items one at a time or select several and import them in bulk. Imports arrive switched off, so nothing runs twice until you activate them.', 'code-snippets')
	},
	viewOnly: {
		step: __('Step 5 of 5', 'code-snippets'),
		title: __('Some items are view only', 'code-snippets'),
		body: __('Code that cannot safely become a snippet, such as server rules, is still listed with where it lives, so you can read it without importing it.', 'code-snippets')
	}
}

const STAGE_STEPS: Partial<Record<DemoStage, keyof typeof STEPS>> = {
	'scan-start': 'scan',
	'scan-themes': 'scan',
	'scan-customizer': 'scan',
	'scan-plugins': 'scan',
	'scan-content': 'scan',
	'results': 'results',
	'sources': 'sources',
	'select': 'import',
	'importing': 'import',
	'imported': 'import',
	'view-only': 'viewOnly'
}

export const getCallout = (stage: DemoStage): CalloutContent | undefined => {
	const step = STAGE_STEPS[stage]
	return step ? STEPS[step] : undefined
}
