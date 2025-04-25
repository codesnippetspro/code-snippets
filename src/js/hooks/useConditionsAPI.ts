import { __, _x } from '@wordpress/i18n'
import { useCallback, useEffect, useMemo, useState } from 'react'
import { REST_API_AXIOS_CONFIG, REST_BASE, REST_CONDITIONS_BASE } from '../utils/restAPI'
import { getSnippetDisplayName, isCondition } from '../utils/snippets/snippets'
import { useAxios } from './useAxios'
import { useSnippetForm } from './useSnippetForm'
import type { UserRoles } from '../types/api/UserRole'
import type { Pages } from '../types/api/Page'
import type { ConditionOperator, ConditionSubject, ConditionSubjects } from '../types/Condition'
import type { SelectGroups, SelectOptions } from '../types/SelectOption'
import type { Posts } from '../types/api/Post'
import type { PostTypes } from '../types/api/PostType'
import type { Categories, PostTags } from '../types/api/Term'
import type { Users } from '../types/api/User'

export const SUBJECT_OPTIONS: SelectGroups<ConditionSubject> = [
	{
		label: _x('Site', 'condition type', 'code-snippets'),
		options: [
			{ value: 'siteArea', label: __('Area', 'code-snippets') }
		]
	},
	{
		label: _x('Snippets', 'condition type', 'code-snippets'),
		options: [
			{ value: 'condition', label: __('Condition', 'code-snippets') }
		]
	},
	{
		label: __('Posts and Pages', 'code-snippets'),
		options: [
			{ value: 'post', label: __('Post', 'code-snippets') },
			{ value: 'page', label: __('Page', 'code-snippets') },
			{ value: 'postType', label: __('Post type', 'code-snippets') },
			{ value: 'category', label: __('Category', 'code-snippets') },
			{ value: 'tag', label: __('Post tag', 'code-snippets') }
		]
	},
	{
		label: __('User', 'code-snippets'),
		options: [
			{ value: 'user', label: __('User', 'code-snippets') },
			{ value: 'userRole', label: __('User role', 'code-snippets') },
			{ value: 'userCap', label: __('User capability', 'code-snippets') },
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

const STATIC_OPTIONS: { [S in ConditionSubject]?: ObjectOptions<S> } = {
	siteArea: [
		{ value: 'global', label: __('Entire site', 'code-snippets') },
		{ value: 'frontend', label: __('Front-end', 'code-snippets') },
		{ value: 'admin', label: __('Administration dashboard', 'code-snippets') }
	],
	authenticated: BOOLEAN_OPTIONS
}

export type ObjectOptions<S extends ConditionSubject> = SelectGroups<ConditionSubjects[S]> | false | undefined

export interface ConditionsAPI {
	fetchSubjectOptions: <S extends ConditionSubject>(subject: S) => Promise<ObjectOptions<S>>
}

const useSubjectFactory = (): { [S in ConditionSubject]?: () => Promise<SelectGroups<ConditionSubjects[S]>> } => {
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
					({ value: cap, label: cap })))

	}), [get])
}

export const useConditionsAPI = (): ConditionsAPI => {
	const factories = useSubjectFactory()
	const { snippet: currentSnippet, snippetsList } = useSnippetForm()
	const [cache, setCache] = useState(STATIC_OPTIONS)

	useEffect(() => {
		setCache(previous => ({
			...previous,
			condition: snippetsList
				?.filter(snippet => isCondition(snippet) && snippet.id !== currentSnippet.id)
				.map(snippet => ({ value: snippet.id, label: getSnippetDisplayName(snippet) })) ?? []
		}))
	}, [currentSnippet, snippetsList])

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
