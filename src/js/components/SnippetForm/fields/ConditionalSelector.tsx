import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippets } from '../../../hooks/useSnippetsAPI'
import { findOptionByValue } from '../../../utils/options'
import type { SelectOptions } from '../../../types/SelectOption'

export const ConditionalSelector: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()
	const snippets = useSnippets()
	const [options, setOptions] = useState<SelectOptions<number>>()

	useEffect(() => {
		if (snippets) {
			setOptions(
				snippets
					.filter(snippet => 'condition' === snippet.scope && snippet.active)
					.map(snippet => ({
						value: snippet.id,
						label: snippet.name
					}))
			)
		}
	}, [snippets])

	return (
		<Select
			options={options}
			isLoading={snippets === undefined}
			isDisabled={isReadOnly}
			value={findOptionByValue(options, snippet.conditional)}
			onChange={option => {
				setSnippet(snippet => ({
					...snippet,
					conditional: option?.value ?? 0
				}))
			}}

		/>
	)
}
