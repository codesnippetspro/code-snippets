import React from 'react'
import { __, _n, sprintf } from '@wordpress/i18n'
import { DemoUpsell } from '../../common/demo/DemoUpsell'
import type { SavedDemoSnippet } from './types'

interface AiAgentDemoUpsellProps {
	snippets: SavedDemoSnippet[]
	onReplay: VoidFunction
}

export const AiAgentDemoUpsell: React.FC<AiAgentDemoUpsellProps> = ({ snippets, onReplay }) => {
	const saved = snippets.filter(snippet => snippet.id && !snippet.error)

	return (
		<DemoUpsell
			title={__('That was a demo — the snippets are real', 'code-snippets')}
			onReplay={onReplay}
		>
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
		</DemoUpsell>
	)
}
