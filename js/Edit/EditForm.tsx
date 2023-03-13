import React, { useEffect, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import apiFetch from '@wordpress/api-fetch'
import { BaseSnippetProps } from '../types/BaseSnippetProps'
import { CodeEditorInstance } from '../types/editor'
import { Snippet } from '../types/Snippet'
import { getSnippetType } from '../utils/snippets'
import { saveSnippet } from './actions'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'
import { DescriptionEditorProps } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { SnippetEditorToolbar } from './SnippetEditor/SnippetEditorToolbar'
import classnames from 'classnames'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { ActionButtons } from './ActionButtons'
import { TagEditor } from './fields/TagEditor'

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

const SnippetEditForm: React.FC<BaseSnippetProps> = ({ snippet, setSnippet }) => {
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()
	const inputProps: BaseSnippetProps = { snippet, setSnippet }

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

			<MultisiteSharingSettings {...inputProps} />
			<DescriptionEditorProps {...inputProps} />
			<TagEditor {...inputProps} />

			<ActionButtons snippet={snippet} />
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
