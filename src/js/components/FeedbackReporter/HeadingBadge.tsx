import React, { useMemo } from 'react'
import { createPortal } from 'react-dom'
import { __ } from '@wordpress/i18n'

export interface HeadingBadgeProps {
	label: string
}

/** The page heading, wherever this screen happens to render one. */
const findHeading = (): Element | null =>
	document.querySelector('.wrap h1.wp-heading-inline') ??
	document.querySelector('#wpbody-content .wrap h1') ??
	document.querySelector('#wpbody-content h1')

export const HeadingBadge: React.FC<HeadingBadgeProps> = ({ label }) => {
	const heading = useMemo(findHeading, [])

	if (!label || !heading) {
		return null
	}

	return createPortal(
		<span
			className="code-snippets-feedback-badge"
			title={__('You are running a pre-release build. Use the Send feedback button if something breaks.', 'code-snippets')}
		>
			<span className="code-snippets-feedback-badge__dot" aria-hidden="true"></span>
			{label}
		</span>,
		heading
	)
}
