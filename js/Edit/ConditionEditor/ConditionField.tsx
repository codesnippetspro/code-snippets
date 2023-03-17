import React, { ReactElement, useMemo } from 'react'
import Select, { Props as SelectProps, OptionsOrGroups, SingleValue } from 'react-select'
import { Condition } from '../../types/Condition'
import { SelectGroup, SelectOption } from '../../types/SelectOption'
import { SnippetInputProps } from '../../types/SnippetInputProps'

const findOption = <T, >(options: OptionsOrGroups<SelectOption<T>, SelectGroup<T>>, value: T): SingleValue<SelectOption<T>> | undefined => {
	for (const option of options) {
		const result = 'value' in option ?
			option.value === value ? option : undefined :
			findOption(option.options, value)

		if (result) {
			return result
		}
	}
}

export interface ConditionFieldProps<F extends keyof Condition> extends SnippetInputProps,
	SelectProps<SelectOption<Condition[F]>, false, SelectGroup<Condition[F]>> {
	field: F
	groupId: string
	conditionId: string
}

export const ConditionField = <F extends keyof Condition>(
	{ field, conditionId, groupId, options, snippet, setSnippet, ...selectProps }: ConditionFieldProps<F>
): ReactElement => {
	const value = useMemo<SingleValue<SelectOption<Condition[F]>> | undefined>(() => {
		const condition = snippet.conditions?.[groupId][conditionId]

		return options && condition ?
			findOption(options, condition[field]) :
			undefined
	}, [conditionId, field, groupId, options, snippet.conditions])

	return (
		<Select
			className="snippet-condition-field-select"
			options={options}
			styles={{ menu: base => ({ ...base, zIndex: 9999 }) }}
			value={value}
			onChange={option => {
				setSnippet(previous => ({
					...previous,
					conditions: {
						...previous.conditions,
						[groupId]: {
							...previous.conditions?.[groupId],
							[conditionId]: { ...previous.conditions?.[groupId][conditionId], [field]: option?.value ?? '' }
						}
					}
				}))
			}}
			{...selectProps}
		/>
	)
}
