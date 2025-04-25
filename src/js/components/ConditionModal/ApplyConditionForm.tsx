import { BaseControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { getSnippetDisplayName, isCondition } from '../../utils/snippets/snippets'
import { Button } from '../common/Button'
import { SingleSelect } from '../common/Select'
import type { SelectOption } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

export interface ApplyConditionFormProps {
	closeModal: VoidFunction
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({ closeModal }) => {
	const { snippet, setSnippet, snippetsList } = useSnippetForm()
	const [options, setOptions] = useState<SelectOption<Snippet['id']>[]>()
	const [conditionId, setConditionId] = useState<Snippet['id'] | undefined>(snippet.conditionId)

	useEffect(() => {
		if (!options && snippetsList) {
			setOptions(snippetsList.filter(isCondition).map(snippet =>
				({ key: snippet.id, value: snippet.id, label: getSnippetDisplayName(snippet) })))
		}
	}, [snippetsList, options])

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()

		// TODO: add validation
		if (conditionId) {
			setSnippet(previous => ({ ...previous, conditionId }))
			closeModal()
		}
	}

	return (
		<form className="modal-form" onSubmit={handleSubmit}>
			<div className="modal-content">
				<BaseControl label={__('Saved Conditions', 'code-snippets')}>
					<SingleSelect
						required
						options={options}
						onChange={newValue => setConditionId(newValue)}
						isLoading={options === undefined}
						currentValue={conditionId}
					/>

				</BaseControl>
			</div>

			<div className="modal-footer">
				<Button className="cancel-button" onClick={() => closeModal()}>
					{__('Cancel', 'code-snippets')}
				</Button>

				<button
					type="submit"
					className="button button-primary button-large"
				>
					{__('Apply', 'code-snippets')}
				</button>
			</div>
		</form>
	)
}
