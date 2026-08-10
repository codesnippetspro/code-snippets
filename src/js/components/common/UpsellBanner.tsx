import { ExternalLink } from '@wordpress/components'
import { createInterpolateElement } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { shouldShowUpsell } from '../../utils/screen'
import { Button } from './Button'

export const UpsellBanner = () => {
	const [isDismissed, setIsDismissed] = useState(false)

	return isDismissed || !shouldShowUpsell()
		? null
		: <div
			className="code-snippets-upsell-banner"
			aria-label={__('Upgrade to Code Snippets Pro', 'code-snippets')}
			role="region"
		>
			<img
				src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`}
				alt={__('Code Snippets logo', 'code-snippets')}
				height="34"
				aria-hidden="true"
			/>
			<p>
				{createInterpolateElement(
					__('Unlock <strong>cloud sync, snippet conditions, AI features</strong> and much more with Code Snippets Pro.', 'code-snippets'),
					{ strong: <strong /> }
				)}
			</p>

			<ExternalLink
				className="button button-primary"
				href="https://codesnippets.pro/pricing/"
			>
				{__('Get Started', 'code-snippets')}
			</ExternalLink>

			<Button small link onClick={() => setIsDismissed(true)} aria-label={__('Dismiss upsell banner', 'code-snippets')}>
				<span className="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</Button>
		</div>
}
