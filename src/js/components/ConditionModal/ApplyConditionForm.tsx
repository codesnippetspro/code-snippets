import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { getSnippetDisplayName, isCondition } from '../../utils/snippets/snippets'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { Select } from '../common/Select'
import { SubmitButton } from '../common/SubmitButton'
import { ConditionEditor } from '../ConditionEditor'
import { DismissibleNotice } from '../DismissableNotice'
import type { SelectOption } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

export interface ModalFooterProps {
	onEdit: VoidFunction
	onClose: VoidFunction
	selectedCondition?: Snippet
	setSelectedCondition: (id?: number) => void
}

const ModalFooter: React.FC<ModalFooterProps> = ({ onClose, onEdit, selectedCondition, setSelectedCondition }) =>
	<div className="modal-footer">
		<Button simple large onClick={onClose}>
			{__('Cancel', 'code-snippets')}
		</Button>

		<div>
			<Button large disabled={!selectedCondition} onClick={() => setSelectedCondition()}>
				{__('Clear', 'code-snippets')}
			</Button>

			<Button large disabled={!selectedCondition} onClick={() => onEdit()}>
				{__('Edit Condition', 'code-snippets')}
			</Button>

			<SubmitButton
				large
				primary
				text={selectedCondition
					? __('Apply Condition', 'code-snippets')
					: __('Confirm', 'code-snippets')}
			/>
		</div>
	</div>

interface ModalUpperProps {
	onEdit: VoidFunction
	selectedCondition?: Snippet
	setSelectedCondition: (id?: number) => void
	options: SelectOption<Snippet['id']>[] | undefined
}

const ModalUpper: React.FC<ModalUpperProps> = ({ onEdit, options, selectedCondition, setSelectedCondition }) =>
	<>
		<div className="modal-content-top">
			<label htmlFor="condition-select">{__('Selected Condition', 'code-snippets')}</label>

			<Button simple onClick={() => {
				setSelectedCondition(undefined)
				onEdit()
			}}>
				{__('Create new condition', 'code-snippets')}
			</Button>
		</div>

		<Select
			id="condition-select"
			className="condition-select"
			isClearable
			options={options}
			onSelect={newValue => setSelectedCondition(newValue)}
			isLoading={options === undefined}
			currentValue={selectedCondition?.id}
		/>
	</>

export interface ApplyConditionFormProps {
	onEdit: VoidFunction
	onClose: VoidFunction
	selectedCondition?: Snippet
	setSelectedCondition: (id?: number) => void
	showConfirmationNotice: boolean
	dismissConfirmationNotice: VoidFunction
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({
	onEdit,
	onClose,
	selectedCondition,
	setSelectedCondition,
	showConfirmationNotice,
	dismissConfirmationNotice
}) => {
	const { setSnippet } = useSnippetForm()
	const { snippetsList } = useSnippetsList()
	const [options, setOptions] = useState<SelectOption<Snippet['id']>[]>()

	useEffect(() => {
		if (!options && snippetsList) {
			setOptions(snippetsList.filter(isCondition).map(snippet =>
				({ key: snippet.id, value: snippet.id, label: getSnippetDisplayName(snippet) })))
		}
	}, [snippetsList, options])

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()

		if (selectedCondition) {
			setSnippet(previous => ({ ...previous, conditionId: selectedCondition.id }))
			onClose()
		}
	}

	return (
		<form className="modal-form" onSubmit={handleSubmit}>
			<div className="modal-content">
				<ModalUpper
					onEdit={onEdit}
					options={options}
					selectedCondition={selectedCondition}
					setSelectedCondition={setSelectedCondition}
				/>

				{showConfirmationNotice
					? <DismissibleNotice className="notice-success" onDismiss={dismissConfirmationNotice}>
						<p>{__('The condition has been saved.', 'code-snippets')}</p>
					</DismissibleNotice> : null}

				{selectedCondition && <ConditionEditor condition={selectedCondition} />}
			</div>

			<ModalFooter {...{ onClose, onEdit, selectedCondition, setSelectedCondition }} />
		</form>
	)
}
