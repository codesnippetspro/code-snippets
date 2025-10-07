import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const RTLControl: React.FC = () => {
	const { codeEditorInstance } = useSnippetForm()

	return (
		<div>
			<h4>
				<label htmlFor="snippet-code-direction">
					{__('Code Direction', 'code-snippets')}
				</label>
			</h4>

			<select id="snippet-code-direction" onChange={event =>
				codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
			}>
				<option value="ltr">{__('LTR', 'code-snippets')}</option>
				<option value="rtl">{__('RTL', 'code-snippets')}</option>
			</select>
		</div>
	)
}
