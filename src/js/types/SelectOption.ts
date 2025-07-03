import type { GroupBase, Options, OptionsOrGroups } from 'react-select'

export interface SelectOption<T> {
	readonly key?: string | number
	readonly value: T
	readonly label: string
}

export type SelectGroup<T> = GroupBase<SelectOption<T>>

export type SelectOptions<T> = Options<SelectOption<T>>

export type SelectGroups<T> = OptionsOrGroups<SelectOption<T>, SelectGroup<T>>
