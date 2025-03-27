import { BaseControl } from '@wordpress/components'
import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import Select from 'react-select'
import { useSnippets } from '../../hooks/useSnippetsAPI'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { CancelButton } from './CancelButton'
import type { SelectOption, SelectOptions } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { FormEventHandler } from 'react'

export interface ApplyConditionFormProps {
	closeModal: VoidFunction
}

export const ApplyConditionForm: React.FC<ApplyConditionFormProps> = ({ closeModal }) => {
	const snippets = useSnippets()
	const { snippet, setSnippet } = useSnippetForm()
	const [selectedOption, setSelectedOption] = useState<SelectOption<Snippet> | undefined>(undefined)

	const options = useMemo<SelectOptions<Snippet>>(() => {
		const newOptions = snippets
			?.filter(snippet => snippet.active && 'condition' === snippet.scope)
			.map(snippet => ({
				value: snippet,
				label: snippet.name
			})) ?? []

		if (snippets && snippet.conditional) {
			setSelectedOption(newOptions.find(option => option.value.id === snippet.conditional))
		}

		return newOptions
	}, [snippets, snippet.conditional])

	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()

		// TODO: add validation
		if (selectedOption) {
			setSnippet(previous => ({
				...previous,
				conditional: selectedOption.value.id
			}))

			closeModal()
		}
	}

	return (
		<form className="modal-form" onSubmit={handleSubmit}>
			<div className="modal-content">
				<BaseControl label={__('Saved Conditions', 'code-snippets')}>
					<Select
						isLoading={snippets === undefined}
						value={selectedOption ?? null}
						options={options}
						onChange={newValue => setSelectedOption(newValue ?? undefined)}
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
