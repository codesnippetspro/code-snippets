import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import SelectBase from 'react-select'
import AsyncSelectBase from 'react-select/async'
import classnames from 'classnames'
import type { SelectGroup, SelectGroups, SelectOption } from '../../types/SelectOption'
import type { ActionMeta, Props, StylesConfig } from 'react-select'

const MIN_SEARCH_LENGTH = 2

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
			} else if (currentValue === option.value) {
				return [option]
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
	searchOptions?: (searchTerm: string) => Promise<SelectGroups<T>>
	fetchSelectedOption?: (value: T) => Promise<SelectOption<T> | null>
}

interface UseFetchMissingOptionsParams<T> {
	currentValue: T | T[] | undefined
	options: SelectGroups<T> | undefined
	fetchSelectedOption: ((value: T) => Promise<SelectOption<T> | null>) | undefined
}

const useFetchMissingOptions = <T, >({
	currentValue,
	options,
	fetchSelectedOption
}: UseFetchMissingOptionsParams<T>): SelectOption<T>[] => {
	const [fetchedOptions, setFetchedOptions] = useState<SelectOption<T>[]>([])
	const fetchedValuesRef = useRef<Set<string>>(new Set())

	useEffect(() => {
		if (!fetchSelectedOption || undefined === currentValue) {
			return
		}

		const values = Array.isArray(currentValue) ? currentValue : [currentValue]
		const foundOptions = findSelectOptions(options, currentValue)

		const missingValues = values.filter(value => {
			const opt: SelectOption<T> = { value, label: '' }
			const valueKey = getOptionValue(opt)
			const isFound = foundOptions.some(foundOpt => getOptionValue(foundOpt) === valueKey)
			const alreadyFetched = fetchedValuesRef.current.has(valueKey)
			return !isFound && !alreadyFetched
		})

		if (0 === missingValues.length) {
			return
		}

		missingValues.forEach(value => {
			const opt: SelectOption<T> = { value, label: '' }
			fetchedValuesRef.current.add(getOptionValue(opt))
		})

		Promise.all(missingValues.map(value => fetchSelectedOption(value)))
			.then(results => {
				const validResults = results.filter((opt): opt is SelectOption<T> => null !== opt)
				if (0 < validResults.length) {
					setFetchedOptions(prev => [...prev, ...validResults])
				}
			})
			.catch(console.error)
	}, [currentValue, options, fetchSelectedOption])

	return fetchedOptions
}

const mergeOptions = <T, >(
	options: SelectGroups<T> | undefined,
	fetchedOptions: SelectOption<T>[]
): SelectGroups<T> | undefined => {
	if (0 === fetchedOptions.length) {
		return options
	}

	const newOptions: (SelectOption<T> | SelectGroup<T>)[] = options ? [...options] : []
	for (const fetched of fetchedOptions) {
		let found = false
		for (const group of newOptions) {
			const opts = 'options' in group ? group.options : [group]
			if (opts.some(opt => getOptionValue(opt) === getOptionValue(fetched))) {
				found = true
				break
			}
		}
		if (!found) {
			newOptions.unshift(fetched)
		}
	}
	return newOptions
}

const useSelectedOptions = <T, >(
	options: SelectGroups<T> | undefined,
	currentValue: T | T[] | undefined,
	fetchedOptions: SelectOption<T>[]
) => {
	const [selectedOptions, setSelectedOptions] = useState<readonly SelectOption<T>[]>(
		() => findSelectOptions(options, currentValue))

	useEffect(() => {
		const foundInOptions = findSelectOptions(options, currentValue)
		const foundInFetched = findSelectOptions(fetchedOptions, currentValue)

		const allFound = [...foundInOptions]
		for (const fetched of foundInFetched) {
			if (!allFound.some(opt => getOptionValue(opt) === getOptionValue(fetched))) {
				allFound.push(fetched)
			}
		}

		setSelectedOptions(allFound)
	}, [options, currentValue, fetchedOptions])

	return { selectedOptions, setSelectedOptions }
}

const useLoadOptions = <T, >(
	searchOptions: ((searchTerm: string) => Promise<SelectGroups<T>>) | undefined,
	displayOptions: SelectGroups<T> | undefined
) =>
	useCallback(
		(inputValue: string, callback: (opts: SelectGroups<T>) => void) => {
			if (!searchOptions || inputValue.length < MIN_SEARCH_LENGTH) {
				callback(displayOptions ?? [])
				return
			}
			searchOptions(inputValue).then(callback).catch(() => callback(displayOptions ?? []))
		},
		[searchOptions, displayOptions]
	)

const useHandleChange = <T, >(
	setSelectedOptions: React.Dispatch<React.SetStateAction<readonly SelectOption<T>[]>>,
	onSelect: ((selected: T | undefined, actionMeta: ActionMeta<SelectOption<T>>) => void) | undefined,
	onSelectMulti: ((selected: T[], actionMeta: ActionMeta<SelectOption<T>>) => void) | undefined
) =>
	useCallback(
		(selected: SelectOption<T> | readonly SelectOption<T>[] | null, actionMeta: ActionMeta<SelectOption<T>>) => {
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
		},
		[setSelectedOptions, onSelect, onSelectMulti]
	)

export const Select = <T, IsMulti extends boolean = false>(props: SelectProps<T, IsMulti>) => {
	const {
		options, isMulti, onSelect, className, isDisabled, currentValue, onSelectMulti, searchOptions, fetchSelectedOption, ...selectProps
	} = props

	const fetchedOptions = useFetchMissingOptions({ currentValue, options, fetchSelectedOption })
	const { selectedOptions, setSelectedOptions } = useSelectedOptions(options, currentValue, fetchedOptions)
	const displayOptions = useMemo(() => mergeOptions(options, fetchedOptions), [options, fetchedOptions])
	const loadOptions = useLoadOptions(searchOptions, displayOptions)
	const handleChange = useHandleChange(setSelectedOptions, onSelect, onSelectMulti)

	const commonProps = {
		styles: buildSelectStyles<T, IsMulti>({ isDisabled }),
		value: isMulti ? selectedOptions : selectedOptions[0] ?? null,
		isMulti,
		className: classnames('code-snippets-select', className),
		isDisabled,
		getOptionLabel: (option: SelectOption<T>) => option.label,
		getOptionValue,
		onChange: handleChange,
		...selectProps
	}

	if (searchOptions) {
		return <AsyncSelectBase {...commonProps} loadOptions={loadOptions} defaultOptions={displayOptions} cacheOptions />
	}

	return <SelectBase {...commonProps} options={displayOptions} />
}
