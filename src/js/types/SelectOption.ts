import type { GroupBase } from 'react-select'

export interface SelectOption<T> {
	readonly value: T
	readonly label: string
}

export type SelectGroup<T> = GroupBase<SelectOption<T>>
