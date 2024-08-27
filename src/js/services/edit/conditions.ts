import { __ } from '@wordpress/i18n'
import axios from 'axios'
import { getRestUrl } from '../../utils/restAPI'
import type { SelectGroups, SelectOption, SelectOptions } from '../../types/SelectOption'
import type { ConditionOperator, ConditionSubject, ConditionSubjects } from '../../types/Condition'

export const SUBJECT_OPTIONS: SelectGroups<ConditionSubject> = [
	{
		label: __('Post', 'code-snippets'),
		options: [
			{ value: 'post', label: __('Current post', 'code-snippets') },
			{ value: 'page', label: __('Current page', 'code-snippets') },
			{ value: 'postType', label: __('Current post type', 'code-snippets') },
			{ value: 'category', label: __('Post category', 'code-snippets') },
			{ value: 'tag', label: __('Post tag', 'code-snippets') }
		]
	},
	{
		label: __('User', 'code-snippets'),
		options: [
			{ value: 'user', label: __('Current user', 'code-snippets') },
			{ value: 'userRole', label: __('Current user role', 'code-snippets') },
			{ value: 'authenticated', label: __('Logged-in', 'code-snippets') }
		]
	}
]

export const OPERATOR_OPTIONS: SelectOptions<ConditionOperator> = [
	{ value: 'eq', label: __('is equal to', 'code-snippets') },
	{ value: 'neq', label: __('is not equal to', 'code-snippets') }
]

const BOOLEAN_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Yes', 'code-snippets') },
	{ value: false, label: __('No', 'code-snippets') }
]

export type ObjectOptions = SelectOptions<string | number | boolean>

const OPTIONS_ENDPOINTS: {
	[S in keyof ConditionSubjects]?: [string, (item: ConditionSubjects[S]) => SelectOption<string | number | boolean>]
} = {
	post: ['/wp/v2/posts', post =>
		({ value: post.id, label: post.title.rendered })],

	page: ['/wp/v2/pages', page =>
		({ value: page.id, label: page.title.rendered })],

	postType: ['/wp/v2/types', postType =>
		({ value: postType.slug, label: postType.name })],

	category: ['/wp/v2/categories', category =>
		({ value: category.id, label: category.name })],

	tag: ['/wp/v2/tags', tag =>
		({ value: tag.id, label: tag.name })],

	user: ['/wp/v2/users', user =>
		({ value: user.id, label: user.name })]
}

const cachedSubjectOptions: Partial<Record<ConditionSubject, ObjectOptions>> = {
	userRole:  [
		{ value: 'administrator', label: __('Administrator', 'code-snippets') },
		{ value: 'editor', label: __('Editor', 'code-snippets') },
		{ value: 'author', label: __('Author', 'code-snippets') },
		{ value: 'contributor', label: __('Contributor', 'code-snippets') },
		{ value: 'subscriber', label: __('Subscriber', 'code-snippets') }
	],
	authenticated: BOOLEAN_OPTIONS
}

// eslint-disable-next-line @typescript-eslint/no-unnecessary-type-parameters
export const fetchSubjectOptions = async <S extends ConditionSubject>(subject: S): Promise<ObjectOptions> => {
	if (cachedSubjectOptions[subject]) {
		return Promise.resolve(cachedSubjectOptions[subject])
	}

	if (!OPTIONS_ENDPOINTS[subject]) {
		return Promise.reject(new Error(`Could not find options for subject: ${subject}`))
	}

	const [endpoint, mapper] = OPTIONS_ENDPOINTS[subject]

	const response = await axios.get<ConditionSubjects[S][]>(getRestUrl(endpoint))
	const items = Object.values(response.data).map(mapper)

	cachedSubjectOptions[subject] = items
	return items
}
