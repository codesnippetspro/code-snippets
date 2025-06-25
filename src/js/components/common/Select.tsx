import React, { useEffect, useState } from 'react'
import SelectBase from 'react-select'
import classnames from 'classnames'
import type { SelectGroup, SelectGroups, SelectOption } from '../../types/SelectOption'
import type { ActionMeta, Props, StylesConfig } from 'react-select'

const findSelectOptions = <T, >(groups?: SelectGroups<T>, currentValue?: T | T[]): SelectOption<T>[] => {
	if (undefined === groups || undefined === currentValue) {
		return []
	}

	const found: SelectOption<T>[] = []

	for (const group of groups) {
		for (const option of 'options' in group ? group.options : [group]) {
			if (Array.isArray(currentValue)) {
				if (currentValue.includes(option.value)) {
					found.push(option)
				}
			} else {
				if (currentValue === option.value) {
					return [option]
				}
			}
		}
	}

	return found
}

const getOptionValue = <T, >(option: SelectOption<T>): string => {
	if (option.key) {
		return 'string' === typeof option.key ? option.key : option.key.toString()
	}

	if ('string' === typeof option.value) {
		return option.value
	}

	if ('number' === typeof option.value) {
		return option.value.toString()
	}

	return JSON.stringify(option.value)
}

const buildSelectStyles = <T, IsMulti extends boolean>({
	isDisabled
}: Pick<SelectProps<T, IsMulti>, 'isDisabled'>): StylesConfig<SelectOption<T>, IsMulti, SelectGroup<T>> => ({
	menu: base => ({ ...base, zIndex: 9999 }),
	control: base => ({ ...base, flexWrap: 'nowrap' }),
	singleValue: base => ({ ...base, overflow: 'visible' }),
	indicatorSeparator: () => ({ display: 'none' }),
	multiValueLabel: base => isDisabled ? { ...base, padding: '3px 6px' } : base,
	dropdownIndicator: base => isDisabled ? { display: 'none' } : base,
	multiValueRemove: base => isDisabled ? { display: 'none' } : base
})

export interface SelectProps<T, IsMulti extends boolean> extends Props<SelectOption<T>, IsMulti, SelectGroup<T>> {
	currentValue?: IsMulti extends true ? T[] : T
	onSelect?: (selected: T | undefined, actionMeta: ActionMeta<SelectOption<T>>) => void
	onSelectMulti?: (selected: T[], actionMeta: ActionMeta<SelectOption<T>>) => void
}

export const Select = <T, IsMulti extends boolean = false>({
	options,
	isMulti,
	onChange,
	onSelect,
	className,
	isDisabled,
	currentValue,
	onSelectMulti,
	...selectProps
}: SelectProps<T, IsMulti>) => {
	const [selectedOptions, setSelectedOptions] = useState<readonly SelectOption<T>[]>(
		() => findSelectOptions(options, currentValue))

	useEffect(() => {
		setSelectedOptions(findSelectOptions(options, currentValue))
	}, [options, currentValue, isMulti])

	return (
		<SelectBase
			styles={buildSelectStyles<T, IsMulti>({ isDisabled })}
			value={isMulti ? selectedOptions : selectedOptions[0] ?? null}
			isMulti={isMulti}
			options={options}
			className={classnames('code-snippets-select', className)}
			isDisabled={isDisabled}
			getOptionLabel={option => option.label}
			getOptionValue={getOptionValue}
			onChange={(selected, actionMeta) => {
				onChange?.(selected, actionMeta)

				if (null === selected) {
					setSelectedOptions([])
					onSelect?.(undefined, actionMeta)
				} else if ('value' in selected) {
					setSelectedOptions([selected])
					onSelect?.(selected.value ?? undefined, actionMeta)
				} else {
					setSelectedOptions(selected)
					onSelectMulti?.(selected.map(option => option.value), actionMeta)
				}
			}}
			{...selectProps}
		/>
	)
}
