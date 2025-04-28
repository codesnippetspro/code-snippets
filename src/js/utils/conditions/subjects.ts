import { __ } from '@wordpress/i18n'
import { REST_BASE, REST_CONDITIONS_BASE } from '../restAPI'
import { getSnippetDisplayName, isCondition } from '../snippets/snippets'
import type { PostStatuses } from '../../types/api/PostStatus'
import type { SelectOptions } from '../../types/SelectOption'
import type { ConditionOperator } from '../../types/Condition'
import type { UserRoles } from '../../types/api/UserRole'
import type { ConditionSubjects, PostConditionSubjects, SiteConditionSubjects, SnippetConditionSubjects, UserConditionSubjects } from '../../types/ConditionSubject'
import type { ConditionSubjectDefinitions } from '../../types/ConditionSubjectDefinitions'
import type { Users } from '../../types/api/User'
import type { Categories, PostTags } from '../../types/api/Term'
import type { PostTypes } from '../../types/api/PostType'
import type { Posts } from '../../types/api/Post'
import type { Pages } from '../../types/api/Page'

const OPERATORS = (() => {
	const date: ConditionOperator[] = ['between', 'before', 'after']
	const single: ConditionOperator[] = ['is', 'not']
	const multiple: ConditionOperator[] = ['is', 'not', 'in', 'not in']
	const boolean: ConditionOperator[] = ['true', 'false']

	return <const> { single, multiple, date, boolean }
})()

const YES_NO_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Yes', 'code-snippets') },
	{ value: false, label: __('No', 'code-snippets') }
]

const ENABLED_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Enabled', 'code-snippets') },
	{ value: false, label: __('Disabled', 'code-snippets') }
]

const SITE_CONDITION_SUBJECTS: ConditionSubjectDefinitions<SiteConditionSubjects> = {
	siteArea: {
		group: 'site',
		label: __('Area', 'code-snippets'),
		operators: OPERATORS.single,
		options: [
			{ value: 'global', label: __('Entire site', 'code-snippets') },
			{ value: 'frontend', label: __('Front-end', 'code-snippets') },
			{ value: 'admin', label: __('Administration dashboard', 'code-snippets') }
		]
	},
	currentQuery: {
		group: 'site',
		label: __('Current query', 'code-snippets'),
		operators: OPERATORS.multiple,
		options: [
			{ value: 'home', label: __('Blog homepage', 'code-snippets') },
			{ value: 'frontpage', label: __('Front page', 'code-snippets') },
			{ value: 'search', label: __('Search results', 'code-snippets') },
			{ value: 'archive', label: __('Archive page', 'code-snippets') },
			{ value: '404', label: __('404 page', 'code-snippets') },
			{ value: 'single', label: __('Single post', 'code-snippets') },
			{ value: 'page', label: __('Page', 'code-snippets') },
			{ value: 'postTypeArchive', label: __('Post type archive', 'code-snippets') }
		]
	},
	debugEnabled: {
		group: 'site',
		label: __('WP_DEBUG mode', 'code-snippets'),
		operators: OPERATORS.single,
		options: ENABLED_OPTIONS
	}
}

const SNIPPETS_CONDITION_SUBJECTS: ConditionSubjectDefinitions<SnippetConditionSubjects> = {
	condition: {
		group: 'snippets',
		label: __('Condition', 'code-snippets'),
		operators: OPERATORS.boolean,
		deriveOptions: (currentSnippet, snippets) =>
			snippets
				?.filter(snippet => isCondition(snippet) && snippet.id !== currentSnippet.id)
				.map(snippet => ({ value: snippet.id, label: getSnippetDisplayName(snippet) })) ?? []
	}
}

const POSTS_CONDITION_SUBJECTS: ConditionSubjectDefinitions<PostConditionSubjects> = {
	post: {
		group: 'posts',
		label: __('Post', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<Posts>(`${REST_BASE}/wp/v2/posts`).then(posts =>
				posts.map(post =>
					({ value: post.id, label: post.title.rendered })))
	},
	page: {
		group: 'posts',
		label: __('Page', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<Pages>(`${REST_BASE}/wp/v2/pages`).then(pages =>
				pages.map(page =>
					({ value: page.id, label: page.title.rendered })))
	},
	postType: {
		group: 'posts',
		label: __('Post type', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<PostTypes>(`${REST_BASE}/wp/v2/types`).then(postTypes =>
				Object.values(postTypes).map(postType =>
					({ value: postType.slug, label: postType.name })))
	},
	category: {
		group: 'posts',
		label: __('Post category', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<Categories>(`${REST_BASE}/wp/v2/categories`).then(categories =>
				categories.map(category =>
					({ value: category.id, label: category.name })))
	},
	tag: {
		group: 'posts',
		label: __('Post tag', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<PostTags>(`${REST_BASE}/wp/v2/tags`).then(tags =>
				tags.map(tag =>
					({ value: tag.id, label: tag.name })))
	},
	postStatus: {
		group: 'posts',
		label: __('Post status', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<PostStatuses>(`${REST_BASE}/wp/v2/statuses`).then(statuses =>
				Object.values(statuses).map(status =>
					({ value: status.slug, label: status.name })))
	},
	postAuthor: {
		group: 'posts',
		label: __('Post author', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<Users>(`${REST_BASE}/wp/v2/users?who=authors&has_published_posts=true&per-page=50`).then(users =>
				users.map(user => ({ value: user.id, label: user.name })))
	}
}

const USER_CONDITION_SUBJECTS: ConditionSubjectDefinitions<UserConditionSubjects> = {
	user: {
		group: 'users',
		label: __('User', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<Users>(`${REST_BASE}/wp/v2/users?per-page=50&orderby=id`).then(users => [{
				label: __('User ID', 'code-snippets'),
				options: users.map(user => ({ value: user.id, label: `${user.id} (${user.name})` }))
			}])
	},
	userRole: {
		group: 'users',
		label: __('User role', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<UserRoles>(`${REST_CONDITIONS_BASE}/roles`).then(roles =>
				roles.map(role =>
					({ value: role.role, label: role.name })))
	},
	userCap: {
		group: 'users',
		label: __('User capability', 'code-snippets'),
		operators: OPERATORS.multiple,
		fetchOptions: api =>
			api.get<string[]>(`${REST_CONDITIONS_BASE}/capabilities`).then(caps =>
				caps.map(cap =>
					({ value: cap, label: cap })))
	},
	authenticated: {
		group: 'users',
		label: __('Logged-in', 'code-snippets'),
		operators: OPERATORS.single,
		options: YES_NO_OPTIONS
	}
}

export const CONDITION_SUBJECTS: ConditionSubjectDefinitions<ConditionSubjects> = {
	...SITE_CONDITION_SUBJECTS,
	...SNIPPETS_CONDITION_SUBJECTS,
	...POSTS_CONDITION_SUBJECTS,
	...USER_CONDITION_SUBJECTS
}
