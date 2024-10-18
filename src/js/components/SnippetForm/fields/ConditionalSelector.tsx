import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippets } from '../../../hooks/useSnippets'
import { findOptionByValue } from '../../../utils/options'
import type { SelectOptions } from '../../../types/SelectOption'
import { getConditionalScope } from '../../../utils/snippets'

export const ConditionalSelector: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()
	const snippets = useSnippets()
	const [options, setOptions] = useState<SelectOptions<number>>()

	useEffect(() => {
		if (snippets) {
			setOptions(
				snippets
					.filter(snippet => 'condition' === snippet.scope)
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
			isDisabled={isReadOnly}
			value={findOptionByValue(options, snippet.conditionalId)}
			onChange={option => {
				setSnippet(snippet => ({
					...snippet,
					scope: getConditionalScope(snippet),
					conditionalId: option?.value
				}))
			}}

		/>
	)
}
