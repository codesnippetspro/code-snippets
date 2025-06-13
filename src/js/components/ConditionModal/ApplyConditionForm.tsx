import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { getSnippetDisplayName, isCondition } from '../../utils/snippets/snippets'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { Select } from '../common/Select'
import { SubmitButton } from '../common/SubmitButton'
import { ConditionEditor } from '../ConditionEditor'
import type { SelectOption } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

const ModalFooter: React.FC<ApplyConditionFormProps> = ({ onClose, onEdit, selectedCondition, setSelectedCondition }) =>
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
				disabled={!selectedCondition}
				text={__('Apply Condition', 'code-snippets')}
			/>
		</div>
	</div>

export interface ApplyConditionFormProps {
	onEdit: VoidFunction
	onClose: VoidFunction
	selectedCondition?: Snippet
	setSelectedCondition: (id?: number) => void
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({ selectedCondition, setSelectedCondition, onClose, onEdit }) => {
	const { setSnippet, snippetsList } = useSnippetForm()
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
				<div className="modal-content-top">
					<label htmlFor="condition-select">{__('Selected Condition', 'code-snippets')}</label>

					<Button simple onClick={() => {
						setSelectedCondition(undefined)
						onEdit()
					}}>
						{__('Create new condition', 'code-snippets')}
					</Button>
				</div>

				<div className="modal-content-mid">
					<Select
						name="condition-select"
						required
						isClearable
						options={options}
						onSelect={newValue => setSelectedCondition(newValue)}
						isLoading={options === undefined}
						currentValue={selectedCondition?.id}
					/>
				</div>

				{selectedCondition
					? <ConditionEditor condition={selectedCondition} />
					: null}
			</div>

			<ModalFooter {...{ onClose, onEdit, selectedCondition, setSelectedCondition }} />
		</form>
	)
}
