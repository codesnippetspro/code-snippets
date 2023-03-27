import { Spinner } from '@wordpress/components'
import React from 'react'
import { __, isRTL } from '@wordpress/i18n'
import { ActionButton } from '../../common/ActionButton'
import { Snippet } from '../../types/Snippet'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { SnippetActionsProps, useSnippetActions } from '../actions'

export interface InlineActionButtonsProps extends SnippetActionsProps {
	snippet: Snippet
	isWorking: boolean
}

export interface CodeEditorToolbarProps extends InlineActionButtonsProps {
	codeEditorInstance: CodeEditorInstance | undefined
}

const RTLControl: React.FC<Pick<CodeEditorToolbarProps, 'codeEditorInstance'>> = ({ codeEditorInstance }) =>
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

const InlineActionButtons: React.FC<InlineActionButtonsProps> = ({ snippet, isWorking, ...actionsProps }) => {
	const actions = useSnippetActions(actionsProps)

	return (
		<>
			{isWorking ? <Spinner /> : ''}

			<ActionButton
				small
				id="save_snippet_extra"
				text={__('Save Changes', 'code-snippets')}
				title={__('Save Snippet', 'code-snippets')}
				onClick={() => actions.submit(snippet)}
				disabled={isWorking}
			/>

			{'single-use' === snippet.scope ?
				<ActionButton
					small
					id="save_snippet_execute_extra"
					text={__('Execute Once', 'code-snippets')}
					title={__('Save Snippet and Execute Once', 'code-snippets')}
					onClick={() => actions.submitAndActivate(snippet, true)}
					disabled={isWorking}
				/> :
				snippet.active ?
					<ActionButton
						small
						id="save_snippet_deactivate_extra"
						text={__('Deactivate', 'code-snippets')}
						title={__('Save Snippet and Deactivate', 'code-snippets')}
						onClick={() => actions.submitAndActivate(snippet, false)}
						disabled={isWorking}
					/> :
					<ActionButton
						small
						id="save_snippet_activate_extra"
						text={__('Activate', 'code-snippets')}
						title={__('Save Snippet and Activate', 'code-snippets')}
						onClick={() => actions.submitAndActivate(snippet, true)}
						disabled={isWorking}
					/>}
		</>
	)
}

export const SnippetEditorToolbar: React.FC<CodeEditorToolbarProps> = ({ codeEditorInstance, ...actionButtonsProps }) =>
	<p className="submit-inline">
		{window.CODE_SNIPPETS_EDIT?.extraSaveButtons ?
			<InlineActionButtons {...actionButtonsProps} /> : ''}

		{isRTL() ?
			<RTLControl codeEditorInstance={codeEditorInstance} /> : ''}
	</p>
