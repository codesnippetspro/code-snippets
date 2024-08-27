import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import type { ReactElement } from 'react'
import type { OptionsOrGroups, Props as SelectProps } from 'react-select'
import type { Condition } from '../../types/Condition'
import type { SelectGroup, SelectOption } from '../../types/SelectOption'

const findOptionByValue = <T, >(optionsOrGroups?: OptionsOrGroups<SelectOption<T>, SelectGroup<T>>, value?: T): SelectOption<T> | undefined => {
	if (optionsOrGroups && value) {
		for (const optionOrGroup of optionsOrGroups) {
			const option: SelectOption<T> | undefined = 'options' in optionOrGroup ?
				optionOrGroup.options.find(option => option.value === value) :
				optionOrGroup

			if (option?.value === value) {
				return option
			}
		}
	}

	return undefined
}

export interface ConditionFieldProps<F extends keyof Condition>
	extends SelectProps<SelectOption<Condition[F]>, false, SelectGroup<Condition[F]>> {
	field: F
	groupId: string
	conditionId: string
}

export const ConditionField = <F extends keyof Condition>({
	field,
	conditionId,
	groupId,
	options,
	...selectProps
}: ConditionFieldProps<F>): ReactElement => {
	const { snippet, setSnippet } = useSnippetForm()
	const condition = snippet.conditions?.[groupId][conditionId]
	const [selectedOption, setSelectedOption] = useState<SelectOption<Condition[F]> | undefined>(() => findOptionByValue(options, condition?.[field]))

	useEffect(() => {
		if (selectedOption && !findOptionByValue(options, selectedOption.value)) {
			setSelectedOption(undefined)
		}

		if (!selectedOption && condition?.[field]) {
			setSelectedOption(findOptionByValue(options, condition[field]))
		}
	}, [selectedOption, options])

	useEffect(() => {
		setSelectedOption(findOptionByValue(options, condition?.[field]))
	}, [condition])

	return (
		<Select
			className="snippet-condition-field-select"
			options={options}
			styles={{ menu: base => ({ ...base, zIndex: 9999 }) }}
			value={selectedOption ?? null}
			getOptionLabel={option => option.label}
			getOptionValue={option => String(option.value)}
			onChange={selected => {
				setSelectedOption(selected ?? undefined)
				setSnippet(previous => ({
					...previous,
					conditions: {
						...previous.conditions,
						[groupId]: {
							...previous.conditions?.[groupId],
							[conditionId]: {
								...previous.conditions?.[groupId][conditionId],
								[field]: selected ? selected.value : undefined
							}
						}
					}
				}))
			}}
			{...selectProps}
		/>
	)
}
