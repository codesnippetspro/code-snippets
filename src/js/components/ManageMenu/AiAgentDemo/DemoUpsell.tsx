import React from 'react'
import { __, _n, sprintf } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import type { SavedDemoSnippet } from './types'

const PRICING_URL = 'https://codesnippets.pro/pricing/'

interface DemoUpsellProps {
	snippets: SavedDemoSnippet[]
	onReplay: VoidFunction
}

export const DemoUpsell: React.FC<DemoUpsellProps> = ({ snippets, onReplay }) => {
	const saved = snippets.filter(snippet => snippet.id && !snippet.error)

	return (
		<div className="ai-agent-demo-upsell">
			<h2 className="ai-agent-demo-upsell__title">
				{__('That was a demo — the snippets are real', 'code-snippets')}
			</h2>

			<p>{__('The whole walkthrough was scripted and ran inside this plugin. Nothing was sent anywhere.', 'code-snippets')}</p>

			{0 < saved.length && <p>{sprintf(
				/* translators: %d: number of snippets saved by the demo. */
				_n(
					'The %d snippet it produced was genuinely saved to your site, inactive and ready to edit.',
					'The %d snippets it produced were genuinely saved to your site, inactive and ready to edit.',
					saved.length,
					'code-snippets'
				),
				saved.length
			)}</p>}

			<p>{__('In Code Snippets Pro, the AI Agent does all of this for whatever you actually ask it to build.', 'code-snippets')}</p>

			<div className="ai-agent-demo-upsell__actions">
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
	)
}
