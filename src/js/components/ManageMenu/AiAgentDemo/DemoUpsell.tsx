import React from 'react'
import { __ } from '@wordpress/i18n'
import { DemoUpsell } from '../../common/demo/DemoUpsell'

export const AiAgentDemoUpsell: React.FC<{ onReplay: VoidFunction }> = ({ onReplay }) =>
	<DemoUpsell
		title={__('That was a demo — the real agent writes for you', 'code-snippets')}
		onReplay={onReplay}
	>
		<p>{__('The whole walkthrough was scripted and ran inside this plugin. No prompt or snippet left your site, and none were added to it.', 'code-snippets')}</p>
		<p>{__('In Code Snippets Pro the AI Agent does all of this for whatever you ask it to build, and the snippets it writes are real — saved to your site inactive, until you have read them and switched them on.', 'code-snippets')}</p>
	</DemoUpsell>
