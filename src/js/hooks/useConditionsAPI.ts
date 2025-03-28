import { __, _x } from '@wordpress/i18n'
import { REST_API_AXIOS_CONFIG, REST_BASE, REST_CONDITIONS_BASE } from '../utils/restAPI'
import { useAxios } from './useAxios'
import type { SelectGroups, SelectOption, SelectOptions } from '../types/SelectOption'
import type { ConditionOperator, ConditionSubject, ConditionSubjects } from '../types/ConditionRule'

export const ENABLED_OPTIONS: SelectGroups<boolean> = [
	{ value: true, label: __('Enable', 'code-snippets') },
	{ value: false, label: __('Disable', 'code-snippets') }
]

export const SUBJECT_OPTIONS: SelectGroups<ConditionSubject> = [
	{
		label: _x('Site', 'condition type', 'code-snippets'),
		options: [
			{ value: 'global', label: __('Entire site', 'code-snippets') },
			{ value: 'frontend', label: __('Site front-end only', 'code-snippets') },
			{ value: 'admin', label: __('Administration area only', 'code-snippets') }
		]
	},
	{
		label: __('Post', 'code-snippets'),
		options: [
			{ value: 'post', label: __('Post', 'code-snippets') },
			{ value: 'page', label: __('Page', 'code-snippets') },
			{ value: 'postType', label: __('Post type', 'code-snippets') },
			{ value: 'category', label: __('Post category', 'code-snippets') },
			{ value: 'tag', label: __('Post tag', 'code-snippets') }
		]
	},
	{
		label: __('User', 'code-snippets'),
		options: [
			{ value: 'user', label: __('User', 'code-snippets') },
			{ value: 'userRole', label: __('User role', 'code-snippets') },
			{ value: 'authenticated', label: __('Logged-in', 'code-snippets') }
		]
	}
]

export const OPERATOR_OPTIONS: SelectOptions<ConditionOperator> = [
	{ value: 'is', label: _x('is', 'condition operator', 'code-snippets') },
	{ value: 'not', label: _x('is not', 'condition operator', 'code-snippets') }
]

const BOOLEAN_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Yes', 'code-snippets') },
	{ value: false, label: __('No', 'code-snippets') }
]

export type ObjectOptions = SelectOptions<string | number | boolean> | false | undefined

const OPTIONS_ENDPOINTS: {
	[S in keyof ConditionSubjects]?: [
		string,
		string,
		(item: ConditionSubjects[S]) => SelectOption<string | number | boolean>
	]
} = {
	post: [REST_BASE, '/wp/v2/posts', post =>
		({ value: post.id, label: post.title.rendered })],

	page: [REST_BASE, '/wp/v2/pages', page =>
		({ value: page.id, label: page.title.rendered })],

	postType: [REST_BASE, '/wp/v2/types', postType =>
		({ value: postType.slug, label: postType.name })],

	category: [REST_BASE, '/wp/v2/categories', category =>
		({ value: category.id, label: category.name })],

	tag: [REST_BASE, '/wp/v2/tags', tag =>
		({ value: tag.id, label: tag.name })],

	user: [REST_BASE, '/wp/v2/users', user =>
		({ value: user.id, label: user.name })],

	userRole: [REST_CONDITIONS_BASE, '/roles', role =>
		({ value: role.role, label: role.name })]
}

const cachedSubjectOptions: Partial<Record<ConditionSubject, ObjectOptions>> = {
	global: false,
	frontend: false,
	admin: false,
	authenticated: BOOLEAN_OPTIONS
}

export interface ConditionsAPI {
	fetchSubjectOptions: <S extends ConditionSubject>(subject: S) => Promise<ObjectOptions>
}

export const useConditionsAPI = (): ConditionsAPI => {
	const { get } = useAxios(REST_API_AXIOS_CONFIG)

	const fetchSubjectOptions = async <S extends ConditionSubject>(subject: S): Promise<ObjectOptions> => {
		if (cachedSubjectOptions[subject] !== undefined) {
			return Promise.resolve(cachedSubjectOptions[subject])
		}

		if (!OPTIONS_ENDPOINTS[subject]) {
			return Promise.reject(new Error(`Could not find options for subject: ${subject}`))
		}

		const [base, endpoint, mapper] = OPTIONS_ENDPOINTS[subject]

		const response = await get<ConditionSubjects[S][]>(`${base}${endpoint}`)
		const items = Object.values(response.data).map(mapper)

		cachedSubjectOptions[subject] = items
		return items
	}

	return { fetchSubjectOptions }
}
