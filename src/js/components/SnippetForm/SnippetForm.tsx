import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { WithSnippetsListContext, useSnippetsList } from '../../hooks/useSnippetsList'
import { SubmitSnippetAction, useSubmitSnippet } from '../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../utils/errors'
import { createSnippetObject, getSnippetType, isCondition, validateSnippet } from '../../utils/snippets/snippets'
import { WithSnippetFormContext, useSnippetForm } from '../../hooks/useSnippetForm'
import { ConfirmDialog } from '../common/ConfirmDialog'
import { EditorSidebar } from '../EditorSidebar'
import { CodeEditor } from './fields/CodeEditor'
import { SnippetLocationInput } from './fields/SnippetLocationInput'
import { SnippetTypeInput } from './fields/SnippetTypeInput'
import { UpgradeDialog } from './page/UpgradeDialog'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { NameInput } from './fields/NameInput'
import { PageHeading } from './page/PageHeading'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../../types/Snippet'

const editFormClassName = ({ snippet, isReadOnly }: { snippet: Snippet, isReadOnly: boolean }) =>
	classnames(
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

interface ConfirmSubmitDialogProps {
	doSubmit: (action: SubmitSnippetAction | undefined) => void
	submitAction: SubmitSnippetAction | undefined
	setSubmitAction: (action: SubmitSnippetAction | undefined) => void
	validationWarning: string | undefined
	setValidationWarning: (warning: string | undefined) => void
}

const ConfirmSubmitDialog: React.FC<ConfirmSubmitDialogProps> = ({
	doSubmit,
	submitAction,
	setSubmitAction,
	validationWarning,
	setValidationWarning
}) =>
	<ConfirmDialog
		open={validationWarning !== undefined}
		title={__('Snippet incomplete', 'code-snippets')}
		confirmLabel={__('Continue', 'code-snippets')}
		onCancel={() => {
			setSubmitAction(undefined)
			setValidationWarning(undefined)
		}}
		onConfirm={() => {
			doSubmit(submitAction)
			setSubmitAction(undefined)
			setValidationWarning(undefined)
		}}
	>
		<p>{`${validationWarning} ${__('Continue?', 'code-snippets')}`}</p>
	</ConfirmDialog>

const EditForm: React.FC<PropsWithChildren> = ({ children }) => {
	const { submitSnippet } = useSubmitSnippet()
	const { snippet, isReadOnly } = useSnippetForm()
	const { refreshSnippetsList } = useSnippetsList()

	const [validationWarning, setValidationWarning] = useState<string | undefined>()
	const [submitAction, setSubmitAction] = useState<SubmitSnippetAction | undefined>()

	const doSubmit = (action?: SubmitSnippetAction) => {
		submitSnippet(action)
			.then(refreshSnippetsList)
			.catch(handleUnknownError)
	}

	const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
		event.preventDefault()

		const action = Object.values(SubmitSnippetAction).find(actionName =>
			actionName === document.activeElement?.getAttribute('name'))

		const validationWarning = validateSnippet(snippet)

		if (validationWarning) {
			setValidationWarning(validationWarning)
			setSubmitAction(action)
		} else {
			doSubmit(action)
		}
	}

	return (
		<>
			<form
				id="snippet-form snippet-sidebar-container"
				method="post"
				onSubmit={handleSubmit}
				className={editFormClassName({ snippet, isReadOnly })}
			>
				{children}
			</form>

			<ConfirmSubmitDialog {...{ doSubmit, submitAction, setSubmitAction, validationWarning, setValidationWarning }} />
		</>
	)
}

const SnippetConditionsEditor: React.FC = () =>
	<div id="snippet_conditions" className="snippet-condition-editor-container">
		{/* TODO */}
		<p>{__('This snippet type is not supported in this version of Code Snippets.')}</p>
	</div>

const ConditionModalButton: React.FC = () =>
	<div>TODO</div>

const EditFormWrap: React.FC = () => {
	const { snippet } = useSnippetForm()
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)

	return (
		<div className="wrap">
			<p><small><a href={window.CODE_SNIPPETS?.urls.manage}>
				{__('Back to all snippets', 'code-snippets')}
			</a></small></p>

			<PageHeading />

			<EditForm>
				<main className="snippet-form-main">
					<NameInput />

					<div className="above-editor-container">
						<SnippetTypeInput openUpgradeDialog={() => setIsUpgradeDialogOpen(true)} />
						<SnippetLocationInput />
						<ConditionModalButton />
					</div>

					{isCondition(snippet)
						? <SnippetConditionsEditor />
						: <CodeEditor />}

					{window.CODE_SNIPPETS_EDIT?.enableDescription ? <DescriptionEditor /> : null}
				</main>

				<EditorSidebar />
			</EditForm>

			<UpgradeDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</div>
	)
}

export const SnippetForm: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsListContext>
			<WithSnippetFormContext initialSnippet={() => createSnippetObject(window.CODE_SNIPPETS_EDIT?.snippet)}>
				<EditFormWrap />
			</WithSnippetFormContext>
		</WithSnippetsListContext>
	</WithRestAPIContext>
