import React from 'react'
import { Spinner } from '@wordpress/components'
import { __, isRTL } from '@wordpress/i18n'
import { SubmitButton } from '../buttons/SubmitButtons'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

const InlineActionButtons: React.FC = () => {
	const { isWorking } = useSnippetForm()

	return (
		<>
			{isWorking ? <Spinner /> : ''}
			<SubmitButton inlineButtons />
		</>
	)
}

const RTLControl: React.FC = () => {
	const { codeEditorInstance } = useSnippetForm()

	return (
		<>
			<label htmlFor="snippet-code-direction" className="screen-reader-text">
				{__('Code Direction', 'code-snippets')}
			</label>

			<select id="snippet-code-direction" onChange={event =>
				codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
			}>
				<option value="ltr">{__('LTR', 'code-snippets')}</option>
				<option value="rtl">{__('RTL', 'code-snippets')}</option>
			</select>
		</>
	)
}

export const SnippetEditorToolbar: React.FC = () =>
	<div className="submit-inline">
		{window.CODE_SNIPPETS_EDIT?.extraSaveButtons ? <InlineActionButtons /> : null}
		{isRTL() ? <RTLControl /> : null}
	</div>
