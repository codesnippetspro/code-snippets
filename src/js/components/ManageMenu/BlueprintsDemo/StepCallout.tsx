import React from 'react'
import { __ } from '@wordpress/i18n'
import type { DemoStage } from './types'

interface CalloutContent {
	step: string
	title: string
	body: string
}

/**
 * What the walkthrough is doing at each stage, shown alongside the form so the
 * visitor can follow the reasoning rather than just watching fields appear.
 */
const CALLOUTS: Partial<Record<DemoStage, CalloutContent>> = {
	general: {
		step: __('Step 1 of 3', 'code-snippets'),
		title: __('Name the shortcode', 'code-snippets'),
		body: __('Every blueprint starts with the basics — the tag you type into a post, and the function that powers it.', 'code-snippets')
	},
	attributes: {
		step: __('Step 2 of 3', 'code-snippets'),
		title: __('Add the attributes', 'code-snippets'),
		body: __('Attributes become the values you pass in, like [staff_profile name="Jane Doe"], each with a fallback default.', 'code-snippets')
	},
	output: {
		step: __('Step 3 of 3', 'code-snippets'),
		title: __('Decide what it renders', 'code-snippets'),
		body: __('This is the PHP that runs whenever the shortcode appears. The blueprint wraps it in everything else it needs.', 'code-snippets')
	},
	generating: {
		step: __('Generating', 'code-snippets'),
		title: __('Building the snippet', 'code-snippets'),
		body: __('Pro turns the whole form into a finished, working snippet — no boilerplate to write yourself.', 'code-snippets')
	},
	generated: {
		step: __('Done', 'code-snippets'),
		title: __('Your snippet is ready', 'code-snippets'),
		body: __('In Pro the finished PHP appears here, ready to review and save to your site.', 'code-snippets')
	}
}

export const StepCallout: React.FC<{ stage: DemoStage }> = ({ stage }) => {
	const callout = CALLOUTS[stage]

	if (!callout) {
		return null
	}

	return (
		<aside className="blueprints-demo-callout" aria-hidden="true">
			<span className="blueprints-demo-callout__step">{callout.step}</span>
			<h3 className="blueprints-demo-callout__title">{callout.title}</h3>
			<p className="blueprints-demo-callout__body">{callout.body}</p>
		</aside>
	)
}
