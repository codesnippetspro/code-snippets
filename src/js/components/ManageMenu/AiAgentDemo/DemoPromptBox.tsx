import React from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { Button } from '../../common/Button'

interface DemoPromptBoxProps {
	value: string
	submitLabel: string
	placeholder: string
	/** Whether the caret should blink at the end of the text. */
	typing: boolean
	/** Whether the walkthrough is clicking the send button right now. */
	pressed?: boolean
}

/**
 * A read-only stand-in for the AI Agent's prompt box. The demo drives the text,
 * so this renders a div rather than a textarea: nothing here accepts input.
 */
export const DemoPromptBox: React.FC<DemoPromptBoxProps> = ({
	value,
	submitLabel,
	placeholder,
	typing,
	pressed = false
}) =>
	<div className={classnames('ai-agent-prompt', 'ai-agent-demo-prompt', { 'is-typing': typing })}>
		<div className="ai-agent-prompt__input ai-agent-demo-prompt__text" aria-live="polite">
			{'' === value
				? <span className="ai-agent-demo-prompt__placeholder">{placeholder}</span>
				: value}
			{typing && <span className="ai-agent-demo-prompt__caret" aria-hidden="true" />}
		</div>

		<div className="ai-agent-prompt__footer">
			<span className="ai-agent-prompt__hint">
				{__('Enter to send · Shift/Ctrl/⌘+Enter for a new line', 'code-snippets')}
			</span>

			<Button
				primary
				type="button"
				className={classnames('ai-agent-demo-send', { 'demo-click': pressed })}
				aria-hidden="true"
				tabIndex={-1}
			>
				{submitLabel}
			</Button>
		</div>
	</div>
