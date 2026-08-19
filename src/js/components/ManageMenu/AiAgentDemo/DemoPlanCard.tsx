import React from 'react'
import { __, _n, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { DEMO_PLAN } from './demoScript'
import { languageToSnippetType } from './types'

interface DemoPlanCardProps {
	/** Whether the "Accept & create" button should render in its pressed state. */
	accepted: boolean
}

export const DemoPlanCard: React.FC<DemoPlanCardProps> = ({ accepted }) => {
	const badge = sprintf(
		/* translators: %d: number of snippets in the bundle. */
		_n('Bundle · %d snippet', 'Bundle · %d snippets', DEMO_PLAN.parts.length, 'code-snippets'),
		DEMO_PLAN.parts.length
	)

	return (
		<div className="ai-agent-plan">
			<div className="ai-agent-plan__header">
				<h3 className="ai-agent-plan__title">{DEMO_PLAN.title}</h3>
				<span className="ai-agent-plan__badge is-bundle">{badge}</span>
			</div>

			<p className="ai-agent-plan__overview">{DEMO_PLAN.summary}</p>

			<h4 className="ai-agent-plan__parts-heading">{__('What will be created', 'code-snippets')}</h4>

			<ul className="ai-agent-plan__parts">
				{DEMO_PLAN.parts.map(part =>
					<li key={part.name} className="ai-agent-plan__part">
						<Badge name={languageToSnippetType(part.language)} small />
						<span className="ai-agent-plan__part-name">{part.name}</span>
						<p className="ai-agent-plan__part-desc">{part.description}</p>
					</li>)}
			</ul>

			<div className="ai-agent-plan__actions">
				<Button
					primary
					type="button"
					className={classnames('ai-agent-demo-accept', { 'is-pressed': accepted })}
					aria-hidden="true"
					tabIndex={-1}
				>
					{__('Accept & create', 'code-snippets')}
				</Button>

				<Button secondary type="button" aria-hidden="true" tabIndex={-1}>
					{__('Refine plan', 'code-snippets')}
				</Button>
			</div>
		</div>
	)
}
