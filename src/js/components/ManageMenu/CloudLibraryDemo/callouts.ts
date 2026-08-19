import { __ } from '@wordpress/i18n'
import type { CalloutContent } from '../../common/demo/DemoCallout'
import type { DemoStage } from './types'

const STEPS: Record<string, CalloutContent> = {
	library: {
		step: __('Step 1 of 4', 'code-snippets'),
		title: __('Your whole cloud, in one place', 'code-snippets'),
		body: __('Every snippet and bundle saved to your Code Snippets Cloud account is listed here, on every site you connect — so work you did once is never more than a click away.', 'code-snippets')
	},
	preview: {
		step: __('Step 2 of 4', 'code-snippets'),
		title: __('Read it before you take it', 'code-snippets'),
		body: __('Preview shows the full code without leaving the page, so you can check exactly what a snippet does before it touches your site.', 'code-snippets')
	},
	download: {
		step: __('Step 3 of 4', 'code-snippets'),
		title: __('Download in one click', 'code-snippets'),
		body: __('The snippet lands in your snippets table straight away — and inactive, so nothing runs until you have looked it over and switched it on.', 'code-snippets')
	},
	synced: {
		step: __('Step 4 of 4', 'code-snippets'),
		title: __('Kept in sync', 'code-snippets'),
		body: __('Once downloaded the row shows as synced, with an edit link instead of a download button. Change it here or in the cloud and the two are kept together.', 'code-snippets')
	}
}

const STAGE_STEPS: Partial<Record<DemoStage, keyof typeof STEPS>> = {
	library: 'library',
	preview: 'preview',
	download: 'download',
	synced: 'synced'
}

export const getCallout = (stage: DemoStage): CalloutContent | undefined => {
	const step = STAGE_STEPS[stage]
	return step ? STEPS[step] : undefined
}
