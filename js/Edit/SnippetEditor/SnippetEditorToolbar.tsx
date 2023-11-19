import React from 'react'
import { Spinner } from '@wordpress/components'
import { __, isRTL } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { isNetworkAdmin } from '../../utils/general'
import { useSnippetForm } from '../SnippetForm/context'

const InlineActivateButton: React.FC = () => {
	const { snippet, isWorking, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()

	if (snippet.shared_network && isNetworkAdmin()) {
		return null
	}

	if ('single-use' === snippet.scope) {
		return (
			<Button
				small
				id="save_snippet_execute_extra"
				title={__('Save Snippet and Execute Once', 'code-snippets')}
				onClick={() => submitAndActivateSnippet()}
				disabled={isWorking}
			>
				{__('Execute Once', 'code-snippets')}
			</Button>
		)
	}

	return snippet.active ?
		<Button
			small
			id="save_snippet_deactivate_extra"
			title={__('Save Snippet and Deactivate', 'code-snippets')}
			onClick={() => submitAndDeactivateSnippet()}
			disabled={isWorking}
		>
			{__('Deactivate', 'code-snippets')}
		</Button> :
		<Button
			small
			id="save_snippet_activate_extra"
			title={__('Save Snippet and Activate', 'code-snippets')}
			onClick={() => submitAndActivateSnippet()}
			disabled={isWorking}
		>
			{__('Activate', 'code-snippets')}
		</Button>
}

const InlineActionButtons: React.FC = () => {
	const { isWorking, submitSnippet } = useSnippetForm()

	return (
		<>
			{isWorking ? <Spinner /> : ''}

			<Button
				small
				id="save_snippet_extra"
				title={__('Save Snippet', 'code-snippets')}
				onClick={() => submitSnippet()}
				disabled={isWorking}
			>
				{__('Save Changes', 'code-snippets')}
			</Button>

			<InlineActivateButton />
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
