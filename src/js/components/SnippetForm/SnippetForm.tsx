import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { createSnippetObject, getSnippetType, isCondition } from '../../utils/snippets'
import { WithSnippetFormContext, useSnippetForm } from '../../hooks/useSnippetForm'
import { ConditionEditor } from '../ConditionEditor'
import { ConditionsModalButton } from '../ConditionModal/ConditionModal'
import { EditorSidebar } from '../EditorSidebar'
import { CodeEditor } from './fields/CodeEditor'
import { SnippetLocationInput } from './fields/SnippetLocationInput'
import { SnippetTypeInput } from './fields/SnippetTypeInput'
import { UpgradeDialog } from './page/UpgradeDialog'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { NameInput } from './fields/NameInput'
import { PageHeading } from './page/PageHeading'

const EditConditionForm: React.FC = () =>
	<div id="snippet_conditions" className="snippet-condition-editor-container">
		<ConditionEditor />
	</div>

const EditForm: React.FC = () => {
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)
	const { snippet, isReadOnly } = useSnippetForm()

	return (
		<div className="wrap">
			<p><small><a href={window.CODE_SNIPPETS?.urls.manage}>
				{__('Back to all snippets', 'code-snippets')}
			</a></small></p>

			<PageHeading />

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
				<main className="snippet-form-main">
					<NameInput />

					<div className="above-editor-container">
						<SnippetTypeInput openUpgradeDialog={() => setIsUpgradeDialogOpen(true)} />
						<SnippetLocationInput />
						<ConditionsModalButton />
					</div>

					<CodeEditor />
					{isCondition(snippet) && <EditConditionForm />}

					{window.CODE_SNIPPETS_EDIT?.enableDescription ? <DescriptionEditor /> : null}
				</main>

				<EditorSidebar />
			</div>

			<UpgradeDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}

export const SnippetForm: React.FC = () =>
	<WithSnippetFormContext initialSnippet={() => createSnippetObject(window.CODE_SNIPPETS_EDIT?.snippet)}>
		<EditForm />
	</WithSnippetFormContext>
