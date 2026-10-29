import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import type { FeedbackReportResponse } from '../../types/Feedback'

export interface FeedbackSuccessProps {
	result: FeedbackReportResponse
}

/** Only a tracker link the plugin published is worth offering as one. */
const isTrackerUrl = (url: string): boolean => url.startsWith('https://github.com/')

export const FeedbackSuccess: React.FC<FeedbackSuccessProps> = ({ result }) =>
	<div className="code-snippets-feedback-success">
		<h3>{__('Report sent', 'code-snippets')}</h3>
		<p>
			{result.reference
				// translators: %s: reference number identifying the report.
				? sprintf(__('Reference %s.', 'code-snippets'), result.reference)
				: __('The team will pick it up from here.', 'code-snippets')}
			{' '}
			{result.url && isTrackerUrl(result.url) &&
				<a href={result.url} target="_blank" rel="noopener noreferrer">
					{__('Track it on GitHub', 'code-snippets')}
				</a>}
		</p>
	</div>
