import type { SelectGroups, SelectOption } from '../types/SelectOption'

export const findOptionByValue = <T>(
	groups: SelectGroups<T> | undefined,
	value: T | undefined
): SelectOption<T> | undefined => {
	if (groups !== undefined && value !== undefined) {
		for (const group of groups) {
			const options = 'options' in group ? group.options : [group]

			for (const option of options) {
				if (option.value === value) {
					return option
				}
			}
		}
	}

	return undefined
}

export const findOptionsByValues = <T>(
	groups: SelectGroups<T> | undefined,
	values: T[] | undefined
): SelectOption<T>[] => {
	const found: SelectOption<T>[] = []

	if (groups !== undefined && values !== undefined) {
		for (const group of groups) {
			const options = 'options' in group ? group.options : [group]

			for (const option of options) {
				if (values.includes(option.value)) {
					found.push(option)
				}
			}
		}
	}

	return found
}
