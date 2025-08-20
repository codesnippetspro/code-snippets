import { ExternalLink } from '@wordpress/components'
import { createInterpolateElement } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { isLicensed } from '../../utils/screen'
import { Button } from './Button'

export const UpsellBanner = () => {
	const [isDismissed, setIsDismissed] = useState(false)

	return isDismissed || isLicensed() || window.CODE_SNIPPETS_EDIT?.hideUpsell
		? null
		: <div className="code-snippets-upsell-banner">
			<img
				src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`}
				alt={__('Code Snippets logo', 'code-snippets')}
				height="34"
			/>
			<p>
				{createInterpolateElement(
					__('Unlock <strong>cloud sync, snippet conditions, AI features</strong> and much more with Code Snippets Pro.', 'code-snippets'),
					{ strong: <strong /> }
				)}
			</p>

			<ExternalLink
				className="button button-primary button-large"
				href="https://codesnippets.pro/pricing/"
			>
				{__('Get Started', 'code-snippets')}
			</ExternalLink>

			<Button small link onClick={() => setIsDismissed(true)}>
				<span className="dashicons dashicons-no-alt"></span>
			</Button>
		</div>
}
