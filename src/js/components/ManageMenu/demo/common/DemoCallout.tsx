import React from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { Button } from '../../../common/Button'

export interface CalloutContent {
	step: string
	title: string
	body: string
}

/**
 * Commentary on what a walkthrough is doing at the current stage, floating in
 * the corner of the viewport so it stays out of the page's flow.
 *
 * The demo pages announce each stage through their own live region, so the
 * commentary is hidden from assistive technology rather than repeating it. The
 * close button stays exposed, since it is the walkthrough's exit.
 */
export interface DemoCalloutProps {
	callout?: CalloutContent
	onClose: VoidFunction
	/** Sit in the page's own flow, beside the walkthrough, rather than floating over it. */
	docked?: boolean
}

export const DemoCallout: React.FC<DemoCalloutProps> = ({ callout, onClose, docked = false }) =>
	callout
		// Keyed on the content rather than the stage: consecutive stages can
		// share a callout, and remounting between them replays the entrance
		// animation as a flicker.
		? <aside key={callout.title} className={classnames('demo-callout', { 'is-docked': docked })}>
			<Button small link className="demo-callout__close" onClick={onClose} aria-label={__('Exit demo', 'code-snippets')}>
				<span className="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</Button>

			<div aria-hidden="true">
				<span className="demo-callout__step">{callout.step}</span>
				<h3 className="demo-callout__title">{callout.title}</h3>
				<p className="demo-callout__body">{callout.body}</p>
			</div>
		</aside>
		: null
