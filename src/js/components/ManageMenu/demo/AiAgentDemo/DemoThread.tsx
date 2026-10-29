import React from 'react'
import { __ } from '@wordpress/i18n'

interface DemoMessageProps {
	speaker: 'user' | 'assistant'
	children: React.ReactNode
}

export const DemoMessage: React.FC<DemoMessageProps> = ({ speaker, children }) =>
	<div className={`ai-agent-message is-${speaker}`}>
		<span className="ai-agent-message__role">
			{'user' === speaker ? __('You', 'code-snippets') : __('AI Agent', 'code-snippets')}
		</span>
		<div className="ai-agent-message__content">{children}</div>
	</div>

export const DemoTyping: React.FC<{ label: string }> = ({ label }) =>
	<div className="ai-agent-typing">
		<span className="ai-agent-typing__dots" aria-hidden="true"><i /><i /><i /></span>
		<span>{label}</span>
	</div>

/**
 * Stand-in for the round trip the real agent makes to Code Snippets Cloud. It
 * carries no state of its own — the parent decides when the trip is in flight.
 */
export const DemoTransit: React.FC<{ label: string }> = ({ label }) =>
	<div className="ai-agent-demo-transit">
		<span className="ai-agent-demo-transit__endpoint">{__('Your site', 'code-snippets')}</span>
		<span className="ai-agent-demo-transit__track" aria-hidden="true">
			<span className="ai-agent-demo-transit__pulse" />
		</span>
		<span className="ai-agent-demo-transit__endpoint">{__('Code Snippets Cloud', 'code-snippets')}</span>
		<span className="screen-reader-text">{label}</span>
	</div>
