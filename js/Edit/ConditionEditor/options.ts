import { __ } from '@wordpress/i18n'
import { AxiosResponse } from 'axios'
import { Options, OptionsOrGroups } from 'react-select'
import { ConditionOperator, ConditionSubject } from '../../types/Condition'
import { SelectGroup, SelectOption } from '../../types/SelectOption'
import { Categories, CATEGORIES_ENDPOINT, PostTags, TAGS_ENDPOINT, Terms } from '../../types/wp/Term'
import { Pages, PAGES_ENDPOINT } from '../../types/wp/Page'
import { Post, Posts, POSTS_ENDPOINT } from '../../types/wp/Post'
import { POST_TYPES_ENDPOINT, PostTypes } from '../../types/wp/PostType'
import { Users, USERS_ENDPOINT } from '../../types/wp/User'
import { apiGet } from '../../utils/api/wp'

export const SUBJECT_OPTIONS: OptionsOrGroups<SelectOption<ConditionSubject>, SelectGroup<ConditionSubject>> = [
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

export const OPERATOR_OPTIONS: Options<SelectOption<ConditionOperator>> = [
	{ value: 'eq', label: __('is equal to', 'code-snippets') },
	{ value: 'neq', label: __('is not equal to', 'code-snippets') }
]

const BOOLEAN_OPTIONS: Options<SelectOption<string>> = [
	{ value: 'yes', label: __('Yes', 'code-snippets') },
	{ value: 'no', label: __('No', 'code-snippets') }
]

const ROLE_OPTIONS: Options<SelectOption<string>> = [
	{ value: 'administrator', label: __('Administrator', 'code-snippets') },
	{ value: 'editor', label: __('Editor', 'code-snippets') },
	{ value: 'author', label: __('Author', 'code-snippets') },
	{ value: 'contributor', label: __('Contributor', 'code-snippets') },
	{ value: 'subscriber', label: __('Subscriber', 'code-snippets') }
]

export type ObjectOptions = Options<SelectOption<number | string>>

const SUBJECT_OPTIONS_CACHE: Partial<Record<ConditionSubject, ObjectOptions>> = {}

const getSubjectOptions = <T>(
	subject: ConditionSubject,
	endpoint: string,
	mapper: (response: AxiosResponse<T>) => ObjectOptions
): Promise<ObjectOptions> => {
	const cached = SUBJECT_OPTIONS_CACHE[subject]
	return cached ?
		Promise.resolve(cached) :
		apiGet<T>(endpoint)
			.then(mapper)
			.then(items => {
				SUBJECT_OPTIONS_CACHE[subject] = items
				return items
			})
}

const mapPosts = (response: AxiosResponse<Pick<Post, 'id' | 'title'>[]>): SelectOption<number>[] =>
	response.data.map(post => <SelectOption<number>> { value: post.id, label: post.title.rendered })

const mapTerms = (response: AxiosResponse<Terms>): SelectOption<number>[] =>
	response.data.map(term => <SelectOption<number>> { value: term.id, label: term.name })

const mapPostTypes = (response: AxiosResponse<PostTypes>): SelectOption<string>[] =>
	Object.values(response.data).map(postType =>
		<SelectOption<string>> { value: postType.slug, label: postType.name })

const mapUsers = (response: AxiosResponse<Users>): SelectOption<number>[] =>
	response.data.map(user =>
		<SelectOption<number>> { value: user.id, label: user.name }
	)

export const SUBJECT_OPTION_PROMISES: Record<ConditionSubject, () => Promise<ObjectOptions>> = {
	post: () =>
		getSubjectOptions<Posts>('post', POSTS_ENDPOINT, mapPosts),
	page: () =>
		getSubjectOptions<Pages>('page', PAGES_ENDPOINT, mapPosts),
	postType: () =>
		getSubjectOptions<PostTypes>('postType', POST_TYPES_ENDPOINT, mapPostTypes),
	category: () =>
		getSubjectOptions<Categories>('category', CATEGORIES_ENDPOINT, mapTerms),
	tag: () =>
		getSubjectOptions<PostTags>('tag', TAGS_ENDPOINT, mapTerms),
	user: () =>
		getSubjectOptions<Users>('user', USERS_ENDPOINT, mapUsers),
	userRole: () =>
		Promise.resolve(ROLE_OPTIONS),
	authenticated: () =>
		Promise.resolve(BOOLEAN_OPTIONS)
}
