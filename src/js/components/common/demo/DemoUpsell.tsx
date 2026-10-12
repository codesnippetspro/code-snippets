import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../Button'
import type { ReactNode } from 'react'

const PRICING_URL = 'https://codesnippets.pro/pricing/'

export interface DemoUpsellProps {
	title: string
	children: ReactNode
	onReplay: VoidFunction
}

/**
 * Closing panel shared by the feature walkthroughs: what the visitor just saw,
 * what the real feature does, and the route to it.
 */
export const DemoUpsell: React.FC<DemoUpsellProps> = ({ title, children, onReplay }) =>
	<div className="demo-upsell">
		<h2 className="demo-upsell__title">{title}</h2>

		{children}

		<div className="demo-upsell__actions">
			<a
				className="button button-primary button-large"
				href={PRICING_URL}
				target="_blank"
				rel="noopener noreferrer"
			>
				{__('Upgrade to Pro', 'code-snippets')}
			</a>

			<Button secondary type="button" onClick={onReplay}>
				{__('Run demo again', 'code-snippets')}
			</Button>
		</div>
	</div>
