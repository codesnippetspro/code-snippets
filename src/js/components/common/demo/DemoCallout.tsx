import React from 'react'

export interface CalloutContent {
	step: string
	title: string
	body: string
}

/**
 * Commentary on what a walkthrough is doing at the current stage, floating in
 * the corner of the viewport so it stays out of the page's flow.
 *
 * The demo pages announce each stage through their own live region, so this is
 * hidden from assistive technology rather than repeating it.
 */
export const DemoCallout: React.FC<{ callout?: CalloutContent }> = ({ callout }) =>
	callout
		// Keyed on the content rather than the stage: consecutive stages can
		// share a callout, and remounting between them replays the entrance
		// animation as a flicker.
		? <aside key={callout.title} className="demo-callout" aria-hidden="true">
			<span className="demo-callout__step">{callout.step}</span>
			<h3 className="demo-callout__title">{callout.title}</h3>
			<p className="demo-callout__body">{callout.body}</p>
		</aside>
		: null
