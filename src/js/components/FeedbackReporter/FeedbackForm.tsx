import React from 'react'
import { SelectControl, TextareaControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { BugDetailFields } from './BugDetailFields'
import { ReportSummaryFields } from './ReportSummaryFields'
import { ReporterFields } from './ReporterFields'
import type { FeedbackReport } from '../../hooks/useFeedbackReport'
import type { FeedbackConfig, FeedbackDraft } from '../../types/Feedback'

const TYPE_OPTIONS = [
	{ label: __('Choose one…', 'code-snippets'), value: '' },
	{ label: __('Bug — something is broken', 'code-snippets'), value: 'bug' },
	{ label: __('Feature request — something is missing', 'code-snippets'), value: 'feature' },
	{ label: __('General feedback', 'code-snippets'), value: 'feedback' }
]

export interface FeedbackFormProps {
	config: FeedbackConfig
	report: FeedbackReport
}

export const FeedbackForm: React.FC<FeedbackFormProps> = ({ config, report }) => {
	const { draft, duplicates, errorMessage, updateDraft } = report
	const type = draft.type

	return <>
		{errorMessage &&
			<p className="code-snippets-feedback-message" role="alert" tabIndex={-1}>{errorMessage}</p>}

		<div className="code-snippets-feedback-field">
			<SelectControl
				label={__('What kind of feedback is this?', 'code-snippets')}
				value={type}
				options={TYPE_OPTIONS}
				onChange={value => updateDraft({ type: value as FeedbackDraft['type'] })}
			/>
		</div>

		{type && <>
			<ReportSummaryFields
				type={type}
				draft={draft}
				duplicates={duplicates}
				updateDraft={updateDraft}
			/>

			{'bug' === type && <BugDetailFields draft={draft} updateDraft={updateDraft} />}

			<div className="code-snippets-feedback-field">
				<TextareaControl
					label={'bug' === type
						? __('Error messages or logs (optional)', 'code-snippets')
						: __('Anything else (optional)', 'code-snippets')}
					rows={3}
					value={draft.comments}
					placeholder={'bug' === type
						? __('Paste any PHP notices, console output or stack traces.', 'code-snippets')
						: ''}
					onChange={comments => updateDraft({ comments })}
				/>
			</div>

			<ReporterFields config={config} draft={draft} updateDraft={updateDraft} />
		</>}
	</>
}
