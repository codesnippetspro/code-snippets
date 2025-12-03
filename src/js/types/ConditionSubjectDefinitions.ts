import { __ } from '@wordpress/i18n'
import type { ConditionSubjects } from './ConditionSubject'
import type { RestAPI } from '../hooks/useRestAPI'
import type { ConditionOperator } from './ConditionGroups'
import type { SelectGroups, SelectOption } from './SelectOption'
import type { Snippet } from './Snippet'

export interface ConditionSubjectDefinition<T> {
	label: string
	group: keyof typeof CONDITIONS_SUBJECT_GROUPS
	operators: ConditionOperator[]
	options?: SelectGroups<T>
	fetchAllOptions?: (restAPI: RestAPI) => Promise<SelectGroups<T>>
	fetchPagedOptions?: (restAPI: RestAPI, page: number) => Promise<SelectGroups<T>>
	searchOptions?: (restAPI: RestAPI, searchTerm: string) => Promise<SelectGroups<T>>
	fetchOptionByValue?: (restAPI: RestAPI, value: T) => Promise<SelectOption<T> | null>
	deriveOptions?: (snippet: Snippet, snippets: readonly Snippet[]) => SelectGroups<T>
	useSubjectOptions?: keyof { [A in keyof ConditionSubjects as ConditionSubjects[A] extends T ? A : never]: A }
}

export type ConditionSubjectDefinitions<T> = { [S in keyof T]: ConditionSubjectDefinition<T[S]> }

export const CONDITIONS_SUBJECT_GROUPS = <const> {
	site: __('Site', 'code-snippets'),
	posts: __('Posts and Pages', 'code-snippets'),
	users: __('Users', 'code-snippets'),
	date: __('Date and Time', 'code-snippets')
}
