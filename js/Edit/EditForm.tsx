import apiFetch from '@wordpress/api-fetch'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useEffect, useState } from 'react'
import { Snippet } from '../types/Snippet'
import { SnippetInputProps } from '../types/SnippetInputProps'
import { CodeEditorInstance } from '../types/WordPressCodeEditor'
import { isNetworkAdmin } from '../utils/general'
import { getSnippetType } from '../utils/snippets'
import { ActionButtons } from './ActionButtons'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'
import { SnippetEditorToolbar } from './SnippetEditor/SnippetEditorToolbar'

const EMPTY_SNIPPET: Snippet = {
	id: 0,
	name: '',
	desc: '',
	code: '',
	tags: [],
	scope: 'global',
	modified: '',
	active: false,
	network: false,
	shared_network: false,
	priority: 10
}

const SnippetEditForm: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) => {
	const options = window.CODE_SNIPPETS_EDIT
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()
	const inputProps: SnippetInputProps = { snippet, setSnippet }

	return (
		<div id="snippet-form" data-snippet-type={getSnippetType(snippet)} className={classnames({
			[`${snippet.scope}-snippet`]: true,
			'new-snippet': !snippet.id,
			'saved-snippet': snippet.id,
			'active-snippet': snippet.active,
			'inactive-snippet': !snippet.active
		})}>
			<NameInput {...inputProps} />

			<SnippetEditorToolbar snippet={snippet} codeEditorInstance={codeEditorInstance} />
			<SnippetEditor
				{...inputProps}
				codeEditorInstance={codeEditorInstance}
				setCodeEditorInstance={setCodeEditorInstance}
			/>

			<div className="below-snippet-editor">
				<ScopeInput {...inputProps} />
				<PriorityInput {...inputProps} />
			</div>

			{isNetworkAdmin() ? <MultisiteSharingSettings {...inputProps} /> : null}
			{options?.enableDescription ? <DescriptionEditor {...inputProps} /> : null}
			{options?.tagOptions.enabled ? <TagsInput {...inputProps} /> : null}

			<ActionButtons {...inputProps} />
		</div>
	)
}

export interface EditFormProps {
	snippetId?: number
}

export const EditForm: React.FC<EditFormProps> = ({ snippetId }) => {
	const [snippet, setSnippet] = useState<Snippet>(EMPTY_SNIPPET)

	useEffect(() => {
		if (0 !== snippetId) {
			apiFetch<Snippet>({ path: `/code-snippets/v1/snippets/${snippetId}` })
				.then(result => setSnippet(result))
		}
	}, [snippetId])

	return 0 !== snippet.id || 0 === snippetId ?
		<SnippetEditForm snippet={snippet} setSnippet={setSnippet} /> :
		<p>{__('Loading snippet editor…', 'code-snippets')}</p>
}
