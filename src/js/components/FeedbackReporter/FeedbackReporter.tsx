import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { FeedbackPanel } from './FeedbackPanel'
import { HeadingBadge } from './HeadingBadge'

export const FeedbackReporter: React.FC = () => {
	const [isOpen, setIsOpen] = useState(false)
	const config = window.CODE_SNIPPETS_FEEDBACK

	if (!config) {
		return null
	}

	// The provider registers a heartbeat listener without cleanup, so it is mounted once
	// for the page rather than on each open.
	return <WithRestAPIContext>
		<HeadingBadge label={config.badge} />

		<button
			type="button"
			className="code-snippets-feedback-launcher"
			aria-expanded={isOpen}
			onClick={() => setIsOpen(true)}
		>
			<span className="code-snippets-feedback-launcher__dot" aria-hidden="true"></span>
			{__('Send feedback', 'code-snippets')}
		</button>

		{isOpen && <FeedbackPanel config={config} onClose={() => setIsOpen(false)} />}
	</WithRestAPIContext>
}
