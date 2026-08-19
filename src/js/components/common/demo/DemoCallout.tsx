import React from 'react'

export interface CalloutContent {
	step: string
	title: string
	body: string
}

/**
 * Commentary on what a walkthrough is doing at the current stage, parked in the
 * gutter beside the page so it never covers the thing it describes.
 *
 * The demo pages announce each stage through their own live region, so this is
 * hidden from assistive technology rather than repeating it.
 */
export const DemoCallout: React.FC<{ callout?: CalloutContent }> = ({ callout }) =>
	callout
		? <aside className="demo-callout" aria-hidden="true">
			<span className="demo-callout__step">{callout.step}</span>
			<h3 className="demo-callout__title">{callout.title}</h3>
			<p className="demo-callout__body">{callout.body}</p>
		</aside>
		: null
