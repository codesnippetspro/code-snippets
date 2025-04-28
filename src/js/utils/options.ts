import type { SelectGroup, SelectOption } from '../types/SelectOption'

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
