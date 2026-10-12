import React from 'react'
import { TextControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { EnvironmentDisclosure } from './EnvironmentDisclosure'
import type { FeedbackConfig, FeedbackDraft } from '../../types/Feedback'

export interface ReporterFieldsProps {
	config: FeedbackConfig
	draft: FeedbackDraft
	updateDraft: (changes: Partial<FeedbackDraft>) => void
}

export const ReporterFields: React.FC<ReporterFieldsProps> = ({ config, draft, updateDraft }) =>
	<>
		<div className="code-snippets-feedback-field">
			<TextControl
				label={__('Your name', 'code-snippets')}
				help={__('Credited on the issue.', 'code-snippets')}
				value={draft.name}
				onChange={name => updateDraft({ name })}
			/>
		</div>

		<div className="code-snippets-feedback-field">
			<TextControl
				type="email"
				label={__('Email for follow-up', 'code-snippets')}
				help={__('Never published.', 'code-snippets')}
				value={draft.email}
				onChange={email => updateDraft({ email })}
			/>
		</div>

		<EnvironmentDisclosure
			summary={config.summary}
			errorCount={(window.codeSnippetsErrors ?? []).length}
		/>
	</>
