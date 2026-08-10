import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

export const RTLControl: React.FC = () => {
	const { codeEditorInstance } = useSnippetForm()
	const directionId = useId()

	return (
		<div className="inline-form-field">
			<label htmlFor={directionId}>
				{__('Code Direction', 'code-snippets')}
			</label>

			<select id={directionId} onChange={event =>
				codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
			}>
				<option value="ltr">{__('LTR', 'code-snippets')}</option>
				<option value="rtl">{__('RTL', 'code-snippets')}</option>
			</select>
		</div>
	)
}
