import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { setEditorDirection } from '../../../../editor/snippetEditor'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

export const RTLControl: React.FC = () => {
	const { editorView } = useSnippetForm()
	const directionId = useId()

	return (
		<div className="inline-form-field">
			<label htmlFor={directionId}>
				{__('Code Direction', 'code-snippets')}
			</label>

			<select id={directionId} onChange={event => {
				if (editorView) {
					setEditorDirection(editorView, 'rtl' === event.target.value ? 'rtl' : 'ltr')
				}
			}}>
				<option value="ltr">{__('LTR', 'code-snippets')}</option>
				<option value="rtl">{__('RTL', 'code-snippets')}</option>
			</select>
		</div>
	)
}
