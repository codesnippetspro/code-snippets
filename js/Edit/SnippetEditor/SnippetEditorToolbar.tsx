import React from 'react'
import { __, isRTL } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { Snippet } from '../../types/Snippet'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { saveSnippet, saveAndActivateSnippet, saveSnippetDeactivate } from '../actions'

export interface CodeEditorToolbarProps {
	snippet: Snippet
	codeEditorInstance: CodeEditorInstance | undefined
}

const RTLControl: React.FC<Pick<CodeEditorToolbarProps, 'codeEditorInstance'>> = ({ codeEditorInstance }) =>
	isRTL() ? <>
		<label htmlFor="snippet-code-direction" className="screen-reader-text">
			{__('Code Direction', 'code-snippets')}
		</label>

		<select id="snippet-code-direction" onChange={event =>
			codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
		}>
			<option value="ltr">{__('LTR', 'code-snippets')}</option>
			<option value="rtl">{__('RTL', 'code-snippets')}</option>
		</select>
	</> : null

const InlineActionButtons: React.FC<Pick<CodeEditorToolbarProps, 'snippet'>> = ({ snippet }) =>
	window.CODE_SNIPPETS_EDIT?.extraSaveButtons ? <>
		<ActionButton
			small
			id="save_snippet_extra"
			text={__('Save Changes', 'code-snippets')}
			title={__('Save Snippet', 'code-snippets')}
			onClick={() => saveSnippet(snippet)}
		/>

		{'single-use' === snippet.scope &&
		<ActionButton
			small
			id="save_snippet_execute_extra"
			text={__('Execute Once', 'code-snippets')}
			title={__('Save Snippet and Execute Once', 'code-snippets')}
			onClick={() => saveAndActivateSnippet(snippet)}
		/>}

		{snippet.active ?
			<ActionButton
				small
				id="save_snippet_deactivate_extra"
				text={__('Deactivate', 'code-snippets')}
				title={__('Save Snippet and Deactivate', 'code-snippets')}
				onClick={() => saveSnippetDeactivate(snippet)}
			/> :
			<ActionButton
				small
				id="save_snippet_activate_extra"
				text={__('Activate', 'code-snippets')}
				title={__('Save Snippet and Activate', 'code-snippets')}
				onClick={() => saveAndActivateSnippet(snippet)}
			/>}
	</> : null

export const SnippetEditorToolbar: React.FC<CodeEditorToolbarProps> = ({ snippet, codeEditorInstance }) =>
	<p className="submit-inline">
		<InlineActionButtons snippet={snippet} />
		<RTLControl codeEditorInstance={codeEditorInstance} />
	</p>
