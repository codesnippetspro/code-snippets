import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { Notices } from '../types/Notice'
import { Snippet } from '../types/Snippet'
import { SnippetActionsInputProps, SnippetInputProps } from '../types/SnippetInputProps'
import { CodeEditorInstance } from '../types/WordPressCodeEditor'
import { isNetworkAdmin } from '../utils/general'
import { createEmptySnippet, getSnippetType, isLicensed, isProSnippet } from '../utils/snippets'
import { ActionButtons } from './components/ActionButtons'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { NoticeList } from './components/NoticeList'
import { PageHeading } from './components/PageHeading'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'
import { SnippetEditorToolbar } from './SnippetEditor/SnippetEditorToolbar'

const OPTIONS = window.CODE_SNIPPETS_EDIT

const getFormClassName = (snippet: Snippet, isReadOnly: boolean): string => classnames(
	'snippet-form',
	`${snippet.scope}-snippet`,
	`${getSnippetType(snippet)}-snippet`,
	`${snippet.id ? 'saved' : 'new'}-snippet`,
	`${snippet.active ? 'active' : 'inactive'}-snippet`,
	{
		'erroneous-snippet': !!snippet.code_error,
		'read-only-snippet': isReadOnly
	}
)

export const EditForm: React.FC = () => {
	const [snippet, setSnippet] = useState<Snippet>(() => OPTIONS?.snippet ?? createEmptySnippet())
	const [notices, setNotices] = useState<Notices>([])
	const [isWorking, setIsWorking] = useState(false)
	const [codeEditorInstance, setCodeEditorInstance] = useState<CodeEditorInstance>()

	const isReadOnly = useMemo(() => !isLicensed() && isProSnippet(snippet.scope), [snippet.scope])
	const inputProps: SnippetInputProps = { snippet, setSnippet, isReadOnly }
	const actionProps: SnippetActionsInputProps = { ...inputProps, isWorking, setNotices, setIsWorking }

	return (
		<div className="wrap">
			<PageHeading {...inputProps} codeEditorInstance={codeEditorInstance} />
			<NoticeList notices={notices} setNotices={setNotices} {...inputProps} />

			<div id="snippet-form" className={getFormClassName(snippet, isReadOnly)}>
				<NameInput {...inputProps} />

				<SnippetEditorToolbar {...actionProps} codeEditorInstance={codeEditorInstance} />
				<SnippetEditor
					{...actionProps}
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
		</div>
	)
}
