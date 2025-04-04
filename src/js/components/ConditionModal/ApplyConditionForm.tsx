import { BaseControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useMemo, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { useSnippets } from '../../hooks/useSnippetsAPI'
import { isCondition } from '../../utils/snippets'
import { Button } from '../common/Button'
import { SingleSelect } from '../common/Select'
import type { SelectOptions } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

export interface ApplyConditionFormProps {
	closeModal: VoidFunction
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({ closeModal }) => {
	const snippets = useSnippets()
	const { snippet, setSnippet } = useSnippetForm()
	const [conditionId, setConditionId] = useState<Snippet['id'] | undefined>(snippet.conditionId)

	const options = useMemo<SelectOptions<Snippet['id']>>(() =>
		snippets
			?.filter(snippet => isCondition(snippet))
			.map(snippet => ({ value: snippet.id, label: snippet.name }))
			?? [],
	[snippets]
	)

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
						isLoading={snippets === undefined}
						currentValue={conditionId}
						options={options}
						onChange={newValue => setConditionId(newValue)}
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
