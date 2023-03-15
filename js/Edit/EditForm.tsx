import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import apiFetch from '@wordpress/api-fetch'
import classnames from 'classnames'
import { Notices } from '../types/Notice'
import { Snippet } from '../types/Snippet'
import { SnippetInputProps } from '../types/SnippetInputProps'
import { CodeEditorInstance } from '../types/WordPressCodeEditor'
import { isNetworkAdmin } from '../utils/general'
import { createEmptySnippet, getSnippetType } from '../utils/snippets'
import { ActionButtons } from './ActionButtons'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'
import { SnippetEditorToolbar } from './SnippetEditor/SnippetEditorToolbar'

const OPTIONS = window.CODE_SNIPPETS_EDIT

const SnippetEditForm: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) => {
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()
	const [notices, setNotices] = useState<Notices>([])
	const [isWorking, setIsWorking] = useState(false)

	const inputProps: SnippetInputProps = { snippet, setSnippet }
	const actionProps = { ...inputProps, isWorking, setNotices, setIsWorking }

	return (
		<>
			{notices.map(([type, message], index) =>
				<div key={message} id="message" className={`notice ${type} fade is-dismissible`}>
					<p>{message}</p>
					<button type="button" className="notice-dismiss" onClick={event => {
						event.preventDefault()
						setNotices(notices.filter((_, i) => index !== i))
					}}>
						<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
					</button>
				</div>
			)}

			<div id="snippet-form" data-snippet-type={getSnippetType(snippet)} className={classnames({
				[`${snippet.scope}-snippet`]: true,
				'new-snippet': !snippet.id,
				'saved-snippet': snippet.id,
				'active-snippet': snippet.active,
				'inactive-snippet': !snippet.active
			})}>
				<NameInput {...inputProps} />

				<SnippetEditorToolbar {...actionProps} codeEditorInstance={codeEditorInstance} />
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
				{OPTIONS?.enableDescription ? <DescriptionEditor {...inputProps} /> : null}
				{OPTIONS?.tagOptions.enabled ? <TagsInput {...inputProps} /> : null}

				<ActionButtons {...actionProps} />
			</div>
		</>
	)
}

export interface EditFormProps {
	snippetId?: number
}

export const EditForm: React.FC<EditFormProps> = ({ snippetId }) => {
	const [snippet, setSnippet] = useState<Snippet>(() => OPTIONS?.snippet ?? createEmptySnippet())

	useEffect(() => {
		if (0 !== snippetId && snippetId !== snippet.id) {
			apiFetch<Snippet>({ path: `/code-snippets/v1/snippets/${snippetId}` })
				.then(result => setSnippet(result))
		}
	}, [snippetId, snippet.id])

	return 0 !== snippet.id || 0 === snippetId ?
		<SnippetEditForm snippet={snippet} setSnippet={setSnippet} /> :
		<p>{__('Loading snippet editor…', 'code-snippets')}</p>
}
