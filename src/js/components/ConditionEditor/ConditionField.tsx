import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { findOptionByValue } from '../../utils/options'
import type { ReactElement } from 'react'
import type { Props as SelectProps } from 'react-select'
import type { Condition } from '../../types/Condition'
import type { SelectGroup, SelectOption } from '../../types/SelectOption'

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
	const currentValue = snippet.conditions?.[groupId][conditionId]?.[field]

	const [selectedOption, setSelectedOption] =
		useState<SelectOption<Condition[F]> | undefined>(() => findOptionByValue(options, currentValue))

	useEffect(() => {
		setSelectedOption(findOptionByValue(options, currentValue))
	}, [options, currentValue])

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
