import React from 'react'
import { Button, Modal } from '@wordpress/components'
import { __, sprintf } from '@wordpress/i18n'
import { useFeedbackReport } from '../../hooks/useFeedbackReport'
import { FeedbackForm } from './FeedbackForm'
import { FeedbackSuccess } from './FeedbackSuccess'
import type { FeedbackConfig } from '../../types/Feedback'

export interface FeedbackPanelProps {
	config: FeedbackConfig
	onClose: VoidFunction
}

export const FeedbackPanel: React.FC<FeedbackPanelProps> = ({ config, onClose }) => {
	const report = useFeedbackReport(config)

	return <Modal
		title={__('Send feedback', 'code-snippets')}
		className="code-snippets-feedback-modal"
		closeButtonLabel={__('Close', 'code-snippets')}
		onRequestClose={onClose}
	>
		<p className="code-snippets-feedback-panel__subtitle">
			{sprintf(
				// translators: 1: plugin version, 2: plugin edition, either Free or Pro.
				__('Code Snippets %1$s %2$s', 'code-snippets'),
				config.version,
				'pro' === config.edition ? __('Pro', 'code-snippets') : __('Free', 'code-snippets')
			)}
		</p>

		<div className="code-snippets-feedback-panel__body">
			{report.result
				? <FeedbackSuccess result={report.result} />
				: <FeedbackForm config={config} report={report} />}
		</div>

		<div className="code-snippets-feedback-panel__footer">
			<Button variant="tertiary" onClick={onClose}>
				{report.result ? __('Close', 'code-snippets') : __('Cancel', 'code-snippets')}
			</Button>

			{!report.result &&
				<Button
					variant="primary"
					disabled={!report.draft.type || report.isSending}
					onClick={report.submit}
				>
					{report.isSending ? __('Sending…', 'code-snippets') : __('Send report', 'code-snippets')}
				</Button>}
		</div>
	</Modal>
}
