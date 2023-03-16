import React, { useState } from 'react'
import classnames from 'classnames'
import { Notices } from '../types/Notice'
import { Snippet } from '../types/Snippet'
import { SnippetActionsInputProps, SnippetInputProps } from '../types/SnippetInputProps'
import { isNetworkAdmin } from '../utils/general'
import { createEmptySnippet, getSnippetType } from '../utils/snippets'
import { ActionButtons } from './ActionButtons'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { NoticeList } from './NoticeList'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'

const OPTIONS = window.CODE_SNIPPETS_EDIT

export const EditForm: React.FC = () => {
	const [snippet, setSnippet] = useState<Snippet>(() => OPTIONS?.snippet ?? createEmptySnippet())
	const [notices, setNotices] = useState<Notices>([])
	const [isWorking, setIsWorking] = useState(false)

	const inputProps: SnippetInputProps = { snippet, setSnippet }
	const actionProps: SnippetActionsInputProps = { ...inputProps, isWorking, setNotices, setIsWorking }

	return (
		<>
			<NoticeList notices={notices} setNotices={setNotices} {...inputProps} />

			<div id="snippet-form" data-snippet-type={getSnippetType(snippet)} className={classnames({
				[`${snippet.scope}-snippet`]: true,
				'new-snippet': !snippet.id,
				'saved-snippet': !!snippet.id,
				'active-snippet': snippet.active,
				'inactive-snippet': !snippet.active,
				'erroneous-snippet': !!snippet.code_error
			})}>
				<NameInput {...inputProps} />
				<SnippetEditor {...actionProps} />

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
