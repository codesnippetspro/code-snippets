import type { SelectGroups, SelectOption } from '../types/SelectOption'

export const findOptionByValue = <T>(options?: SelectGroups<T>, value?: T): SelectOption<T> | undefined => {
	if (options && value) {
		for (const group of options) {
			const found = ('options' in group ? group.options : [group])
				.find(option => option.value === value)

			if (found) {
				return found
			}
		}
	}

	return undefined
}
