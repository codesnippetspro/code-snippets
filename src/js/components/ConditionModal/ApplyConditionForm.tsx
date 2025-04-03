import { BaseControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useMemo, useState } from 'react'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { useSnippets } from '../../hooks/useSnippetsAPI'
import { SingleSelect } from '../common/Select'
import { CancelButton } from './CancelButton'
import type { SelectOptions } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

export interface ApplyConditionFormProps {
	closeModal: VoidFunction
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({ closeModal }) => {
	const snippets = useSnippets()
	const { snippet, setSnippet } = useSnippetForm()
	const [conditionalId, setConditionalId] = useState<Snippet['id'] | undefined>(snippet.conditional)

	const options = useMemo<SelectOptions<Snippet['id']>>(() =>
		snippets
			?.filter(snippet => 'condition' === snippet.scope)
			.map(snippet => ({ value: snippet.id, label: snippet.name }))
			?? [],
	[snippets]
	)

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()

		// TODO: add validation
		if (conditionalId) {
			setSnippet(previous => ({ ...previous, conditional: conditionalId }))
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
						currentValue={conditionalId}
						options={options}
						onChange={newValue => setConditionalId(newValue)}
					/>

				</BaseControl>
			</div>

			<div className="modal-footer">
				<CancelButton closeModal={closeModal} />

				<button
					className="button button-primary button-large"
				>
					{__('Apply', 'code-snippets')}
				</button>
			</div>
		</form>
	)
}
