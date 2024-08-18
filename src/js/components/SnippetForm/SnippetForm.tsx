import React, { useState } from 'react'
import classnames from 'classnames'
import { isNetworkAdmin } from '../../utils/general'
import { createEmptySnippet, getSnippetType } from '../../utils/snippets'
import { WithSnippetFormContext, useSnippetForm } from '../../hooks/useSnippetForm'
import { ActionButtons } from './buttons/ActionButtons'
import { UpgradeDialog } from './page/UpgradeDialog'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { MultisiteSharingSettings } from './fields/MultisiteSharingSettings'
import { NameInput } from './fields/NameInput'
import { PriorityInput } from './fields/PriorityInput'
import { ScopeInput } from './fields/ScopeInput'
import { TagsInput } from './fields/TagsInput'
import { Notices } from './page/Notices'
import { PageHeading } from './page/PageHeading'
import { SnippetEditor } from './SnippetEditor/SnippetEditor'
import { SnippetEditorToolbar } from './SnippetEditor/SnippetEditorToolbar'

const OPTIONS = window.CODE_SNIPPETS_EDIT

const EditForm: React.FC = () => {
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)
	const { snippet, isReadOnly } = useSnippetForm()

	return (
		<div className="wrap">
			<PageHeading />
			<Notices />

			<div id="snippet-form" className={classnames(
				'snippet-form',
				`${snippet.scope}-snippet`,
				`${getSnippetType(snippet.scope)}-snippet`,
				`${snippet.id ? 'saved' : 'new'}-snippet`,
				`${snippet.active ? 'active' : 'inactive'}-snippet`,
				{
					'erroneous-snippet': !!snippet.code_error,
					'read-only-snippet': isReadOnly
				}
			)}>
				<NameInput />

				<SnippetEditorToolbar />
				<SnippetEditor openUpgradeDialog={() => setIsUpgradeDialogOpen(true)} />

				<div className="below-snippet-editor">
					<ScopeInput />
					<PriorityInput />
				</div>

				{isNetworkAdmin() ? <MultisiteSharingSettings /> : null}
				{OPTIONS?.enableDescription ? <DescriptionEditor /> : null}
				{OPTIONS?.tagOptions.enabled ? <TagsInput /> : null}

				<ActionButtons />
			</div>

			<UpgradeDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}

export const SnippetForm: React.FC = () =>
	<WithSnippetFormContext initialSnippet={() => OPTIONS?.snippet ?? createEmptySnippet()}>
		<EditForm />
	</WithSnippetFormContext>
