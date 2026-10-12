import React from 'react'
import { CheckboxControl, TextareaControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import type { FeedbackDraft } from '../../types/Feedback'

export interface BugDetailFieldsProps {
	draft: FeedbackDraft
	updateDraft: (changes: Partial<FeedbackDraft>) => void
}

export const BugDetailFields: React.FC<BugDetailFieldsProps> = ({ draft, updateDraft }) =>
	<>
		<div className="code-snippets-feedback-field">
			<TextareaControl
				label={__('Steps to reproduce', 'code-snippets')}
				rows={5}
				value={draft.steps}
				placeholder={__('1. Open a saved snippet\n2. Click the Conditions tab\n3. Switch back to Code', 'code-snippets')}
				onChange={steps => updateDraft({ steps })}
			/>
		</div>

		<fieldset className="code-snippets-feedback-fieldset">
			<legend>{__('Isolating the problem', 'code-snippets')}</legend>

			<CheckboxControl
				label={__('This happens with only Code Snippets active', 'code-snippets')}
				checked={draft.isolation.plugin_only}
				onChange={pluginOnly => updateDraft({ isolation: { ...draft.isolation, plugin_only: pluginOnly } })}
			/>

			<CheckboxControl
				label={__('This happens with a blank theme active, such as Twenty Twenty-Five', 'code-snippets')}
				checked={draft.isolation.blank_theme}
				onChange={blankTheme => updateDraft({ isolation: { ...draft.isolation, blank_theme: blankTheme } })}
			/>

			<CheckboxControl
				label={__('I can reproduce this consistently using the steps above', 'code-snippets')}
				checked={draft.isolation.reproducible}
				onChange={reproducible => updateDraft({ isolation: { ...draft.isolation, reproducible } })}
			/>
		</fieldset>
	</>
