import React from 'react'
import { __ } from '@wordpress/i18n'

const EXAMPLES: readonly string[] = [
	__('Replace a plugin with snippets', 'code-snippets'),
	__('Speed up my site', 'code-snippets'),
	__('Add a feature', 'code-snippets'),
	__('Fix a problem', 'code-snippets'),
	__('Customize appearance', 'code-snippets')
]

const CAPABILITIES: readonly string[] = [
	__('Plans before it writes, so you approve the shape of the work first.', 'code-snippets'),
	__('Reads your existing snippet library and site environment for context.', 'code-snippets'),
	__('Builds bundles of PHP, CSS, JS, and HTML snippets in one pass.', 'code-snippets'),
	__('Refines what it built, as many times as you need.', 'code-snippets'),
	__('Keeps every conversation so you can pick a build back up later.', 'code-snippets')
]

/**
 * The sidebar the real agent uses, rendered inert. The prompt chips and usage
 * meters are here for the shape of the page, not to be operated.
 */
export const DemoSidebar: React.FC = () =>
	<aside className="ai-agent-sidebar" aria-label={__('AI Agent tools', 'code-snippets')}>
		<div className="ai-agent-sidebar__panel">
			<div className="ai-agent-examples">
				<h2 className="ai-agent-sidebar__heading">{__('Prompt examples', 'code-snippets')}</h2>

				{EXAMPLES.map(example =>
					<button key={example} type="button" className="ai-agent-examples__chip" disabled>
						{example}
					</button>)}
			</div>

			<div className="ai-agent-demo-capabilities">
				<h2 className="ai-agent-sidebar__heading">{__('What the AI Agent does', 'code-snippets')}</h2>

				<ul className="ai-agent-demo-capabilities__list">
					{CAPABILITIES.map(capability => <li key={capability}>{capability}</li>)}
				</ul>
			</div>
		</div>
	</aside>
