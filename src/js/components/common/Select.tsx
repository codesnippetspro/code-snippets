import React, { useEffect, useState } from 'react'
import Select from 'react-select'
import classnames from 'classnames'
import { findOptionByValue, findOptionsByValues } from '../../utils/options'
import type { SelectGroup, SelectOption } from '../../types/SelectOption'
import type { Props } from 'react-select'

const SelectBase = <T, IsMulti extends boolean>({
	className,
	...selectProps
}: Props<SelectOption<T>, IsMulti, SelectGroup<T>>) =>
	<Select
		className={classnames('code-snippets-select', className)}
		styles={{
			menu: base => ({ ...base, zIndex: 9999 }),
			indicatorSeparator: () => ({ display: 'none' })
		}}
		getOptionLabel={option => option.label}
		getOptionValue={option => String(option.key ?? option.value)}
		{...selectProps}
	/>

export type SelectProps<T, IsMulti extends boolean = boolean> = Omit<Props<SelectOption<T>, IsMulti, SelectGroup<T>>, 'isMulti' | 'onChange'>

export interface SingleSelectProps<T> extends SelectProps<T, false> {
	currentValue?: T
	onChange: (selectedValue: T | undefined) => void
}

export const SingleSelect = <T, >({
	options,
	onChange,
	currentValue,
	...selectProps
}: SingleSelectProps<T>) => {
	const [selectedOption, setSelectedOption] = useState<SelectOption<T> | null>(
		() => findOptionByValue(options, currentValue) ?? null)

	useEffect(() => {
		setSelectedOption(findOptionByValue(options, currentValue) ?? null)
	}, [options, currentValue])

	return (
		<SelectBase
			{...selectProps}
			isMulti={false}
			value={selectedOption ?? null}
			options={options}
			onChange={selected => {
				setSelectedOption(selected)
				onChange(selected?.value ?? undefined)
			}}
		/>
	)
}

export interface MultiSelectProps<T> extends SelectProps<T, true> {
	currentValue: T[]
	onChange: (values: T[]) => void
}

export const MultiSelect = <T, >({
	options,
	onChange,
	currentValue,
	...selectProps
}: MultiSelectProps<T>) => {
	const [selectedOptions, setSelectedOptions] = useState<readonly SelectOption<T>[]>(
		() => findOptionsByValues(options, currentValue))

	useEffect(() => {
		setSelectedOptions(findOptionsByValues(options, currentValue))
	}, [options, currentValue])

	return (
		<SelectBase
			{...selectProps}
			isMulti={true}
			value={selectedOptions}
			options={options}
			onChange={selected => {
				setSelectedOptions(selected)
				onChange(selected.map(option => option.value))
			}}
		/>
	)
}
