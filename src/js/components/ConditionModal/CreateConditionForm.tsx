import React, { useState } from 'react'
import { BaseControl, Spinner } from '@wordpress/components'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { createSnippetObject } from '../../utils/snippets'
import { Tooltip } from '../common/Tooltip'
import { ConditionEditor } from '../ConditionEditor'
import { CancelButton } from './CancelButton'
import type { Snippet } from '../../types/Snippet'
import type { Dispatch, FormEventHandler, SetStateAction } from 'react'

const getSnippetName = (snippet: Snippet): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(__('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

const generateConditionNameForSnippet = (snippet: Snippet) =>
	// translators: %s: snippet name.
	sprintf(__('Condition for "%s"', 'code-snippets'), getSnippetName(snippet))

interface ConditionNameFieldProps {
	isSubmitting: boolean
	conditionName: string
	setConditionName: Dispatch<SetStateAction<string>>
}

const ConditionNameField: React.FC<ConditionNameFieldProps> = ({ conditionName, setConditionName, isSubmitting }) =>
	<>
		<Tooltip>
			{__('Give a name to this condition so you can reuse it on other snippets. Leave blank for an auto-generated name.', 'code-snippets')}
		</Tooltip>

		<input
			type="text"
			disabled={isSubmitting}
			className="condition-title-input"
			placeholder={__('Add a title for this condition', 'code-snippets')}
			value={conditionName}
			onChange={event => setConditionName(event.target.value)}
		/>
	</>

interface ModalFooterProps extends ConditionNameFieldProps {
	closeModal: VoidFunction
}

const ModalFooter: React.FC<ModalFooterProps> = ({ closeModal, isSubmitting, conditionName, setConditionName }) =>
	<div className="modal-footer">
		<CancelButton closeModal={closeModal} />

		<div>
			{isSubmitting && <Spinner />}

			<ConditionNameField
				isSubmitting={isSubmitting}
				conditionName={conditionName}
				setConditionName={setConditionName}
			/>

			<button
				type="submit"
				className="button button-primary button-large"
				disabled={isSubmitting}
			>
				{__('Save and Apply', 'code-snippets')}
			</button>
		</div>
	</div>

export interface CreateConditionFormProps {
	closeModal: VoidFunction
}

export const CreateConditionForm: React.FC<CreateConditionFormProps> = ({ closeModal }) => {
	const { snippet, setSnippet } = useSnippetForm()
	const api = useSnippetsAPI()

	const [error, setError] = useState(false)
	const [conditionName, setConditionName] = useState('')
	const [isSubmitting, setIsSubmitting] = useState(false)

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
		setIsSubmitting(true)

		const conditionSnippet: Snippet = createSnippetObject({
			name: '' === conditionName.trim() ? generateConditionNameForSnippet(snippet) : conditionName,
			conditions: snippet.conditions,
			tags: snippet.tags,
			scope: 'condition',
			active: true
		})

		api.create(conditionSnippet)
			.then(result => {
				setSnippet(previous => ({ ...previous, conditional: result.id }))
				setIsSubmitting(false)
				closeModal()
			})
			.catch((error: unknown) => {
				console.error('Error creating condition', error)
				setIsSubmitting(false)
				setError(true)
			})
	}

	return (
		<form className="modal-form" onSubmit={handleSubmit}>
			<div className="modal-content">
				<BaseControl label={__('Set Conditions', 'code-snippets')}>
					<ConditionEditor />
				</BaseControl>

				{error && <div className="notice notice-error">{__('An unknown error occurred. Please try again later', 'code-snippets')}</div>}
			</div>

			<ModalFooter
				closeModal={closeModal}
				isSubmitting={isSubmitting}
				conditionName={conditionName}
				setConditionName={setConditionName}
			/>
		</form>
	)
}
