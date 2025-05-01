import { __ } from '@wordpress/i18n'
import type { AxiosAPI } from '../hooks/useAxios'
import type { ConditionOperator } from './Condition'
import type { SelectGroups } from './SelectOption'
import type { Snippet } from './Snippet'

export interface ConditionSubjectDefinition<T> {
	label: string
	group: keyof typeof CONDITIONS_SUBJECT_GROUPS
	operators: ConditionOperator[]
	options?: SelectGroups<T>
	fetchOptions?: (api: AxiosAPI) => Promise<SelectGroups<T>>
	deriveOptions?: (snippet: Snippet, snippets: readonly Snippet[]) => SelectGroups<T>
}

export type ConditionSubjectDefinitions<T> = { [S in keyof T]: ConditionSubjectDefinition<T[S]> }

export const CONDITIONS_SUBJECT_GROUPS = <const> {
	site: __('Site', 'code-snippets'),
	snippets: __('Snippets', 'code-snippets'),
	posts: __('Posts and Pages', 'code-snippets'),
	users: __('Users', 'code-snippets')
}
