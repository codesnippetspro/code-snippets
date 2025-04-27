import type { SelectGroup, SelectGroups, SelectOption } from '../types/SelectOption'

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

export const buildOptionGroups = <T, O, G extends string>({ groups, items, getGroup, buildOption }: {
	items: T[],
	groups: Record<G, string>,
	getGroup: (item: T) => G,
	buildOption: (item: T) => SelectOption<O> | undefined
}): SelectGroup<O>[] => {
	const optionGroups = new Map<G, SelectOption<O>[]>

	for (const item of items) {
		const option = buildOption(item)

		if (option) {
			const group = getGroup(item)
			const optionGroup = optionGroups.get(group)

			if (optionGroup) {
				optionGroup.push(option)
			} else {
				optionGroups.set(group, [option])
			}
		}
	}

	return [...optionGroups].map(([group, options]) =>
		({ label: groups[group], options }))
}
