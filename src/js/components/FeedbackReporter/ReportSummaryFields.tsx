import React from 'react'
import { TextControl, TextareaControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { DuplicateReports } from './DuplicateReports'
import type { DuplicateReport, FeedbackDraft, FeedbackType } from '../../types/Feedback'

/** What the description asks for, and an example answer, for each kind of report. */
const DESCRIPTION_COPY: Record<FeedbackType, { label: string, placeholder: string }> = {
	bug: {
		label: __('What went wrong?', 'code-snippets'),
		placeholder: __('The editor stopped highlighting PHP and the Save button did nothing.', 'code-snippets')
	},
	feature: {
		label: __('What would you like Code Snippets to do?', 'code-snippets'),
		placeholder: __('Let me tag snippets so I can filter large libraries by project.', 'code-snippets')
	},
	feedback: {
		label: __('What is on your mind?', 'code-snippets'),
		placeholder: __('The new Conditions tab is much clearer, but the icons are hard to tell apart.', 'code-snippets')
	}
}

export interface ReportSummaryFieldsProps {
	type: FeedbackType
	draft: FeedbackDraft
	duplicates: DuplicateReport[]
	updateDraft: (changes: Partial<FeedbackDraft>) => void
}

export const ReportSummaryFields: React.FC<ReportSummaryFieldsProps> = ({
	type,
	draft,
	duplicates,
	updateDraft
}) =>
	<>
		<div className="code-snippets-feedback-field">
			<TextControl
				label={__('Title', 'code-snippets')}
				help={__('A one-line summary. This becomes the issue title.', 'code-snippets')}
				maxLength={120}
				value={draft.title}
				placeholder={__('Syntax highlighting stops after switching editor tabs', 'code-snippets')}
				onChange={title => updateDraft({ title })}
			/>
		</div>

		<DuplicateReports reports={duplicates} />

		<div className="code-snippets-feedback-field">
			<TextareaControl
				label={DESCRIPTION_COPY[type].label}
				rows={4}
				value={draft.description}
				placeholder={DESCRIPTION_COPY[type].placeholder}
				onChange={description => updateDraft({ description })}
			/>
		</div>
	</>
