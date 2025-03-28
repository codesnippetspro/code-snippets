import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import classnames from 'classnames'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { updateConditionField } from '../../utils/conditions'
import { findOptionByValue } from '../../utils/options'
import type { ReactElement } from 'react'
import type { Props as SelectProps } from 'react-select'
import type { ConditionRule } from '../../types/ConditionRule'
import type { SelectGroup, SelectOption } from '../../types/SelectOption'

export interface ConditionFieldEditorProps<F extends keyof ConditionRule>
	extends SelectProps<SelectOption<ConditionRule[F]>, false, SelectGroup<ConditionRule[F]>> {
	field: F
	ruleId: string
	fallbackValue?: ConditionRule[F]
}

export const ConditionFieldEditor = <F extends keyof ConditionRule>({
	field,
	ruleId,
	options,
	fallbackValue,
	...selectProps
}: ConditionFieldEditorProps<F>): ReactElement => {
	const { snippet, setSnippet } = useSnippetForm()
	const currentValue = snippet.conditions[ruleId]?.[field] ?? fallbackValue

	const [selectedOption, setSelectedOption] = useState<SelectOption<ConditionRule[F]> | undefined>(
		() => findOptionByValue(options, currentValue))

	useEffect(() => {
		setSelectedOption(findOptionByValue(options, currentValue))
	}, [options, currentValue])

	return (
		<Select
			className={classnames('code-snippets-select', 'snippet-condition-field-select', `snippet-condition-${field}-select`)}
			options={options}
			styles={{
				menu: base => ({ ...base, zIndex: 9999 }),
				indicatorSeparator: () => ({ display: 'none' }),
				singleValue: ({ maxWidth, position, top, transform, ...other }) => ({ ...other })
			}}
			value={selectedOption ?? null}
			getOptionLabel={option => option.label}
			getOptionValue={option => String(option.value)}
			onChange={selected => {
				setSelectedOption(selected ?? undefined)
				setSnippet(previous => updateConditionField(previous, ruleId, field, selected ? selected.value : undefined))
			}}
			{...selectProps}
		/>
	)
}
