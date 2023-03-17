import React, { Dispatch, ReactElement, SetStateAction, useMemo } from 'react'
import Select, { Props as SelectProps, OptionsOrGroups, SingleValue } from 'react-select'
import { Condition, ConditionGroups } from '../../types/Condition'
import { SelectGroup, SelectOption } from '../../types/SelectOption'
import { Snippet } from '../../types/Snippet'

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

export interface ConditionFieldProps<F extends keyof Condition>
	extends SelectProps<SelectOption<Condition[F]>, false, SelectGroup<Condition[F]>> {
	field: F
	group: keyof ConditionGroups
	condition: Condition
	conditionId: string
	setSnippet: Dispatch<SetStateAction<Snippet>>
}

export const ConditionField = <F extends keyof Condition>(
	{ field, conditionId, group, condition, options, setSnippet, ...selectProps }: ConditionFieldProps<F>
): ReactElement => {
	const value = useMemo<SingleValue<SelectOption<Condition[F]>> | undefined>(
		() => options ? findOption(options, condition[field]) : undefined,
		[condition, field, options]
	)

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
						[group]: { ...previous.conditions?.[group], [conditionId]: { ...condition, [field]: option?.value ?? '' } }
					}
				}))
			}}
			{...selectProps}
		/>
	)
}
