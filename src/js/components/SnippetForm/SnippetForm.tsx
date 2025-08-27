import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { WithSnippetsListContext, useSnippetsList } from '../../hooks/useSnippetsList'
import { SubmitSnippetAction, useSubmitSnippet } from '../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../utils/errors'
import { createSnippetObject, getSnippetType, isCondition, validateSnippet } from '../../utils/snippets/snippets'
import { WithSnippetFormContext, useSnippetForm } from '../../hooks/useSnippetForm'
import { ConfirmDialog } from '../common/ConfirmDialog'
import { UpsellDialog } from '../common/UpsellDialog'
import { EditorSidebar } from '../EditorSidebar'
import { UpsellBanner } from '../common/UpsellBanner'
import { SnippetTypeInput } from './fields/SnippetTypeInput'
import { TagsEditor } from './fields/TagsEditor'
import { CodeEditor } from './fields/CodeEditor'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { NameInput } from './fields/NameInput'
import { PageHeading } from './page/PageHeading'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../../types/Snippet'

const editFormClassName = ({ snippet, isReadOnly, isExpanded }: {
	snippet: Snippet,
	isReadOnly: boolean,
	isExpanded: boolean
}) =>
	classnames(
		'snippet-form',
		isExpanded ? 'snippet-form-expanded' : 'snippet-form-collapsed',
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

interface EditFormProps extends PropsWithChildren {
	className?: string
}

const EditForm: React.FC<EditFormProps> = ({ children, className }) => {
	const { submitSnippet } = useSubmitSnippet()
	const { snippet } = useSnippetForm()
	const { refreshSnippetsList } = useSnippetsList()

	const [validationWarning, setValidationWarning] = useState<string | undefined>()
	const [submitAction, setSubmitAction] = useState<SubmitSnippetAction | undefined>()

	const doSubmit = (action?: SubmitSnippetAction) => {
		submitSnippet(action)
			.then(response => {
				if (response && 0 !== response.id && window.CODE_SNIPPETS) {
					if (window.location.href.toString().includes(window.CODE_SNIPPETS.urls.addNew)) {
						document.title = document.title
							.replace(__('Add New Snippet', 'code-snippets'), __('Edit Snippet', 'code-snippets'))
							.replace(__('Add New Condition', 'code-snippets'), __('Edit Condition', 'code-snippets'))

						const newUrl = addQueryArgs(window.CODE_SNIPPETS.urls.edit, { id: response.id })
						window.history.pushState({}, document.title, newUrl)
					}
				}
			})
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
			<form id="snippet-form" method="post" onSubmit={handleSubmit} className={className}>
				{children}
			</form>

			<ConfirmSubmitDialog {...{ doSubmit, submitAction, setSubmitAction, validationWarning, setValidationWarning }} />
		</>
	)
}

const ConditionsEditor: React.FC = () => {
	const { snippet } = useSnippetForm()

	return isCondition(snippet)
		? <div id="snippet_conditions" className="snippet-condition-editor-container">
			<p>{__('This snippet type is not supported in this version of Code Snippets.')}</p>
		</div>
		: null
}

const EditFormWrap: React.FC = () => {
	const { snippet, isReadOnly } = useSnippetForm()
	const [isExpanded, setIsExpanded] = useState(false)
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)

	return (
		<div className="wrap">
			<p><small>
				{isCondition(snippet)
					? <a href={addQueryArgs(window.CODE_SNIPPETS?.urls.manage, { type: 'cond' })}>
						{__('Back to all conditions', 'code-snippets')}
					</a>
					: <a href={window.CODE_SNIPPETS?.urls.manage}>
						{__('Back to all snippets', 'code-snippets')}
					</a>}
			</small></p>

			<PageHeading />

			<EditForm className={editFormClassName({ snippet, isReadOnly, isExpanded })}>
				<main className="snippet-form-upper">
					<div className="snippet-name-wrapper">
						<NameInput />
						<SnippetTypeInput setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
					</div>

					<CodeEditor {...{ isExpanded, setIsExpanded }} />
					<ConditionsEditor />
				</main>

				<div className="snippet-form-lower">
					<UpsellBanner />
					<DescriptionEditor />
					<TagsEditor />
				</div>

				<EditorSidebar setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
			</EditForm>

			<UpsellDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
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
