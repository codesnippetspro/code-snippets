import React, { useEffect, useRef, useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { WithSnippetsListContext, useSnippetsList } from '../../../hooks/useSnippetsList'
import { SubmitSnippetAction, useSubmitSnippet } from '../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../utils/errors'
import { createSnippetObject, getSnippetType, isCondition, validateSnippet } from '../../../utils/snippets/snippets'
import { buildUrl } from '../../../utils/urls'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { Toolbar } from '../../common/Toolbar'
import { UpsellBanner } from '../../common/UpsellBanner'
import { UpsellDialog } from '../../common/UpsellDialog'
import { EditorSidebar } from '../EditorSidebar'
import { WithSnippetFormContext, useSnippetForm } from './WithSnippetFormContext'
import { SnippetTypeInput } from './fields/SnippetTypeInput'
import { TagsEditor } from './fields/TagsEditor'
import { CodeEditor } from './fields/CodeEditor'
import { DescriptionEditor } from './fields/DescriptionEditor'
import { NameInput } from './fields/NameInput'
import { Notices } from './page/Notices'
import { PageHeading } from './page/PageHeading'
import type { PropsWithChildren } from 'react'
import type { Snippet } from '../../../types/Snippet'

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
		submitSnippet(snippet, action)
			.then(response => {
				if (response && 0 !== response.id && window.CODE_SNIPPETS) {
					if (window.location.href.includes(window.CODE_SNIPPETS.urls.addNew)) {
						document.title = document.title
							.replace(__('Create New Snippet', 'code-snippets'), __('Edit Snippet', 'code-snippets'))
							.replace(__('Create New Condition', 'code-snippets'), __('Edit Condition', 'code-snippets'))

						const newUrl = buildUrl(window.CODE_SNIPPETS.urls.edit, { id: response.id })
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

			<ConfirmSubmitDialog
				{...{ doSubmit, submitAction, setSubmitAction, validationWarning, setValidationWarning }}
			/>
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

const useReloadOnPopState = (isDirty: boolean) => {
	const currentUrl = useRef(window.location.href)
	const skipNextUnloadPrompt = useRef(false)

	useEffect(() => {
		currentUrl.current = window.location.href
	})

	useEffect(() => {
		const handleBeforeUnload = (event: BeforeUnloadEvent) => {
			if (skipNextUnloadPrompt.current) {
				skipNextUnloadPrompt.current = false
				return
			}

			event.preventDefault()
			// Required by Chrome and Edge versions before 119.
			// eslint-disable-next-line @typescript-eslint/no-deprecated
			event.returnValue = true
		}

		if (isDirty) {
			window.addEventListener('beforeunload', handleBeforeUnload)
		}

		return () => window.removeEventListener('beforeunload', handleBeforeUnload)
	}, [isDirty])

	useEffect(() => {
		const handlePopState = () => {
			if (isDirty && !window.confirm(
				__('You have unsaved changes. Leave this page and discard them?', 'code-snippets')
			)) {
				window.history.pushState({}, document.title, currentUrl.current)
				return
			}

			skipNextUnloadPrompt.current = isDirty
			window.location.reload()
		}

		window.addEventListener('popstate', handlePopState)
		return () => window.removeEventListener('popstate', handlePopState)
	}, [isDirty])
}

const EditFormWrap: React.FC = () => {
	const { snippet, isReadOnly, isDirty } = useSnippetForm()
	const [isExpanded, setIsExpanded] = useState(false)
	const [isUpgradeDialogOpen, setIsUpgradeDialogOpen] = useState(false)

	useReloadOnPopState(isDirty)

	return (
		<>
			<PageHeading />
			<Notices placement="above-form" />

			<EditForm className={editFormClassName({ snippet, isReadOnly, isExpanded })}>
				<div className="snippet-form-upper">
					<div className="snippet-name-wrapper">
						<NameInput />
						<SnippetTypeInput setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
					</div>

					<CodeEditor {...{ isExpanded, setIsExpanded }} />
					<ConditionsEditor />
				</div>

				<div className="snippet-form-lower">
					<UpsellBanner />
					<DescriptionEditor />
					<TagsEditor />
				</div>

				<EditorSidebar setIsUpgradeDialogOpen={setIsUpgradeDialogOpen} />
			</EditForm>

			<UpsellDialog isOpen={isUpgradeDialogOpen} setIsOpen={setIsUpgradeDialogOpen} />
		</>
	)
}

export const SnippetForm: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsAPIContext>
			<WithSnippetsListContext>
				<WithSnippetFormContext
					initialSnippet={() => createSnippetObject(window.CODE_SNIPPETS_EDIT?.snippet)}
				>
					<Toolbar />
					<EditFormWrap />
				</WithSnippetFormContext>
			</WithSnippetsListContext>
		</WithSnippetsAPIContext>
	</WithRestAPIContext>
