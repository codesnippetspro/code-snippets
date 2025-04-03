import { __, _x } from '@wordpress/i18n'
import { useCallback, useMemo, useState } from 'react'
import { REST_API_AXIOS_CONFIG, REST_BASE, REST_CONDITIONS_BASE } from '../utils/restAPI'
import { isNetworkAdmin } from '../utils/screen'
import { useAxios } from './useAxios'
import { useSnippetsAPI } from './useSnippetsAPI'
import type { UserRoles } from '../types/wp/UserRole'
import type { Pages } from '../types/wp/Page'
import type { ConditionOperator, ConditionSubject, ConditionSubjects } from '../types/Condition'
import type { SelectGroups, SelectOptions } from '../types/SelectOption'
import type { Posts } from '../types/wp/Post'
import type { PostTypes } from '../types/wp/PostType'
import type { Categories, PostTags } from '../types/wp/Term'
import type { Users } from '../types/wp/User'

export const SUBJECT_OPTIONS: SelectGroups<ConditionSubject> = [
	{
		label: _x('Site', 'condition type', 'code-snippets'),
		options: [
			{ value: 'global', label: __('For the __entire site__', 'code-snippets') },
			{ value: 'frontend', label: __('For the __front-end__ only', 'code-snippets') },
			{ value: 'admin', label: __('For the __administration area__ only', 'code-snippets') }
		]
	},
	{
		label: _x('Condition', 'condition type', 'code-snippets'),
		options: [
			{ value: 'condition', label: __('When a snippet __condition__ applies', 'code-snippets') }
		]
	},
	{
		label: __('Post', 'code-snippets'),
		options: [
			{ value: 'post', label: __('When the current __post__ is', 'code-snippets') },
			{ value: 'page', label: __('When the current __page__ is', 'code-snippets') },
			{ value: 'postType', label: __('When the current __post type__ is', 'code-snippets') },
			{ value: 'category', label: __('When the post has one of these __categories__', 'code-snippets') },
			{ value: 'tag', label: __('When the post has one of these __tags__', 'code-snippets') }
		]
	},
	{
		label: __('User', 'code-snippets'),
		options: [
			{ value: 'user', label: __('When the current logged-in __user__ is', 'code-snippets') },
			{ value: 'userRole', label: __('When the current user is one of these __roles__', 'code-snippets') },
			{ value: 'userCap', label: __('When the current user has one of these __capabilities__', 'code-snippets') },
			{ value: 'authenticated', label: __('If the visitor is __logged-in__', 'code-snippets') }
		]
	}
]

export const OPERATOR_OPTIONS: SelectOptions<ConditionOperator> = [
	{ value: 'is', label: _x('is', 'condition operator', 'code-snippets')},
	{ value: 'not', label: _x('is not', 'condition operator', 'code-snippets')}
]

const BOOLEAN_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Yes', 'code-snippets') },
	{ value: false, label: __('No', 'code-snippets') }
]

export type ObjectOptions<S extends ConditionSubject> = SelectGroups<ConditionSubjects[S]> | false | undefined

export interface ConditionsAPI {
	fetchSubjectOptions: <S extends ConditionSubject>(subject: S) => Promise<ObjectOptions<S>>
}

const useSubjectFactory = (): { [S in ConditionSubject]?: () => Promise<SelectGroups<ConditionSubjects[S]>> } => {
	const { fetchAll } = useSnippetsAPI()
	const { get } = useAxios(REST_API_AXIOS_CONFIG)

	return useMemo(() => ({
		post: () =>
			get<Posts>(`${REST_BASE}/wp/v2/posts`).then(posts =>
				posts.map(post =>
					({ value: post.id, label: post.title.rendered }))),

		page: () =>
			get<Pages>(`${REST_BASE}/wp/v2/pages`).then(pages =>
				pages.map(page =>
					({ value: page.id, label: page.title.rendered }))),

		postType: () =>
			get<PostTypes>(`${REST_BASE}/wp/v2/types`).then(postTypes =>
				Object.values(postTypes).map(postType =>
					({ value: postType.slug, label: postType.name }))),

		category: () =>
			get<Categories>(`${REST_BASE}/wp/v2/categories`).then(categories =>
				categories.map(category =>
					({ value: category.id, label: category.name }))),

		tag: () =>
			get<PostTags>(`${REST_BASE}/wp/v2/tags`).then(tags =>
				tags.map(tag =>
					({ value: tag.id, label: tag.name }))),

		user: () =>
			get<Users>(`${REST_BASE}/wp/v2/users`).then(users => [
				{
					label: __('User ID', 'code-snippets'),
					options: users
						.map(user => ({ value: user.id, label: `${user.id} (${user.name})` }))
						.toSorted((a, b) => a.value - b.value)
				}
			]),

		userRole: () =>
			get<UserRoles>(`${REST_CONDITIONS_BASE}/roles`).then(roles =>
				roles.map(role =>
					({ value: role.role, label: role.name }))),

		userCap: () =>
			get<string[]>(`${REST_CONDITIONS_BASE}/capabilities`).then(caps =>
				caps.map(cap =>
					({ value: cap, label: cap }))),

		condition: () =>
			fetchAll(isNetworkAdmin()).then(snippets =>
				snippets
					.filter(snippet => 'condition' === snippet.scope)
					.map(snippet =>
						({ value: snippet.id, label: snippet.name.trim() ? snippet.name : `Condition #${snippet.id}` })))
	}), [get, fetchAll])
}

export const useConditionsAPI = (): ConditionsAPI => {
	const factories = useSubjectFactory()

	const [cache, setCache] = useState<{ [S in ConditionSubject]?: ObjectOptions<S> }>(() => ({
		global: false,
		frontend: false,
		admin: false,
		authenticated: BOOLEAN_OPTIONS
	}))

	const fetchObjectOptions = useCallback(async <S extends ConditionSubject>(subject: S): Promise<ObjectOptions<S>> => {
		if (cache[subject] !== undefined) {
			return Promise.resolve(cache[subject])
		}

		if (!factories[subject]) {
			return Promise.reject(new Error(`Could not find options for subject: ${subject}`))
		}

		const items = await factories[subject]()
		setCache(previous => ({ ...previous, [subject]: items }))

		return items
	}, [cache, factories])

	return { fetchSubjectOptions: fetchObjectOptions }
}
