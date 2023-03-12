import React from 'react'
import { __, isRTL } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { CodeEditorInstance } from '../../types/editor'
import { Snippet } from '../../types/Snippet'

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
			name="save_snippet"
			text={__('Save Changes', 'code-snippets')}
			title={__('Save Snippet', 'code-snippets')}
		/>

		{'single-use' === snippet.scope &&
		<ActionButton
			small
			id="save_snippet_execute_extra"
			name="save_snippet_execute"
			text={__('Execute Once', 'code-snippets')}
			title={__('Save Snippet and Execute Once', 'code-snippets')}
		/>}

		{snippet.active ?
			<ActionButton
				small
				id="save_snippet_deactivate_extra"
				name="save_snippet_deactivate"
				text={__('Deactivate', 'code-snippets')}
				title={__('Save Snippet and Deactivate', 'code-snippets')}
			/> :
			<ActionButton
				small
				id="save_snippet_activate_extra"
				name="save_snippet_activate"
				text={__('Activate', 'code-snippets')}
				title={__('Save Snippet and Activate', 'code-snippets')}
			/>}
	</> : null

export const SnippetEditorToolbar: React.FC<CodeEditorToolbarProps> = ({ snippet, codeEditorInstance }) =>
	<p className="submit-inline">
		<InlineActionButtons snippet={snippet} />
		<RTLControl codeEditorInstance={codeEditorInstance} />
	</p>
