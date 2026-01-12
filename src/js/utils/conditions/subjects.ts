import { __ } from '@wordpress/i18n'
import { REST_BASE, REST_CONDITIONS_BASE } from '../restAPI'
import type { PluginsSchema } from '../../types/schema/PluginSchema'
import type { ThemesSchema } from '../../types/schema/ThemeSchema'
import type { ConditionSubjects, DateConditionSubjects, PostConditionSubjects, SiteConditionSubjects, UserConditionSubjects } from '../../types/ConditionSubject'
import type { LocalesSchema } from '../../types/schema/LocaleSchema'
import type { PostStatusesSchema } from '../../types/schema/PostStatusSchema'
import type { SelectOptions } from '../../types/SelectOption'
import type { ConditionOperator } from '../../types/ConditionGroups'
import type { UserRoles } from '../../types/schema/UserRole'
import type { ConditionSubjectDefinitions } from '../../types/ConditionSubjectDefinitions'
import type { UsersSchema } from '../../types/schema/UserSchema'
import type { Categories, PostTags } from '../../types/schema/TermSchema'
import type { PostTypesSchema } from '../../types/schema/PostTypeSchema'
import type { PostsSchema } from '../../types/schema/PostSchema'
import type { PagesSchema } from '../../types/schema/PageSchema'
import type { RestAPI } from '../../hooks/useRestAPI'

export const CONDITION_OPERATORS = (() => {
	const date: ConditionOperator[] = ['between', 'before', 'after']
	const single: ConditionOperator[] = ['is', 'not']
	const many: ConditionOperator[] = ['in', 'not in']
	const multiple: ConditionOperator[] = ['is', 'not', 'in', 'not in']
	const boolean: ConditionOperator[] = ['true', 'false']

	return <const> { single, many, multiple, date, boolean }
})()

const YES_NO_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Yes', 'code-snippets') },
	{ value: false, label: __('No', 'code-snippets') }
]

const ENABLED_OPTIONS: SelectOptions<boolean> = [
	{ value: true, label: __('Enabled', 'code-snippets') },
	{ value: false, label: __('Disabled', 'code-snippets') }
]

const MAX_PAGE_OPTIONS_REQUESTS = 50
const pageOptionsRequestCache = new Map<string, Promise<SelectOptions<number>>>()

const MAX_CONDITION_OPTIONS_REQUESTS = 100
const conditionOptionsRequestCache = new Map<string, Promise<unknown>>()

const fetchCached = <T>(cacheKey: string, factory: () => Promise<T>): Promise<T> => {
	const cached = <Promise<T> | undefined> conditionOptionsRequestCache.get(cacheKey)
	if (cached) {
		return cached
	}

	const request = factory().catch((error: unknown) => {
		conditionOptionsRequestCache.delete(cacheKey)
		const rejection = error instanceof Error ? error : new Error(String(error))
		return Promise.reject(rejection)
	})

	conditionOptionsRequestCache.set(cacheKey, request)

	if (conditionOptionsRequestCache.size > MAX_CONDITION_OPTIONS_REQUESTS) {
		const oldestKey = conditionOptionsRequestCache.keys().next().value
		if (oldestKey) {
			conditionOptionsRequestCache.delete(oldestKey)
		}
	}

	return request
}

const fetchPageOptions = (api: RestAPI, url: string): Promise<SelectOptions<number>> => {
	const cached = pageOptionsRequestCache.get(url)
	if (cached) {
		return cached
	}

	const request = api.get<PagesSchema>(url)
		.then(pages =>
			pages.map(page =>
				({ value: page.id, label: page.title.rendered })))
		.catch((error: unknown) => {
			pageOptionsRequestCache.delete(url)
			const rejection = error instanceof Error ? error : new Error(String(error))
			return Promise.reject(rejection)
		})

	pageOptionsRequestCache.set(url, request)

	if (pageOptionsRequestCache.size > MAX_PAGE_OPTIONS_REQUESTS) {
		const oldestKey = pageOptionsRequestCache.keys().next().value
		if (oldestKey) {
			pageOptionsRequestCache.delete(oldestKey)
		}
	}

	return request
}

const SITE_CONDITION_SUBJECTS: ConditionSubjectDefinitions<SiteConditionSubjects> = {
	siteArea: {
		group: 'site',
		label: __('Area', 'code-snippets'),
		operators: CONDITION_OPERATORS.single,
		options: [
			{ value: 'global', label: __('Entire site', 'code-snippets') },
			{ value: 'frontend', label: __('Front-end', 'code-snippets') },
			{ value: 'admin', label: __('Administration dashboard', 'code-snippets') }
		]
	},
	currentQuery: {
		group: 'site',
		label: __('Current query', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
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
	activePlugin: {
		group: 'site',
		label: __('Active plugins', 'code-snippets'),
		operators: CONDITION_OPERATORS.many,
		fetchAllOptions: api => {
			const url = `${REST_CONDITIONS_BASE}/plugins`
			return fetchCached(url, () =>
				api.get<PluginsSchema>(url).then(plugins =>
					plugins.map(plugin =>
						({ value: plugin.filename, label: plugin.name }))))
		}
	},
	currentTheme: {
		group: 'site',
		label: __('Current theme', 'code-snippets'),
		operators: CONDITION_OPERATORS.many,
		fetchAllOptions: api => {
			const url = `${REST_CONDITIONS_BASE}/themes`
			return fetchCached(url, () =>
				api.get<ThemesSchema>(url).then(themes =>
					themes.map(theme =>
						({ value: theme.stylesheet, label: theme.name }))))
		}
	},
	visitorLanguage: {
		group: 'site',
		label: __('Visitor language', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		useSubjectOptions: 'userLocale'
	},
	debugEnabled: {
		group: 'site',
		label: __('WP_DEBUG mode', 'code-snippets'),
		operators: CONDITION_OPERATORS.single,
		options: ENABLED_OPTIONS
	}
}

const POSTS_CONDITION_SUBJECTS: ConditionSubjectDefinitions<PostConditionSubjects> = {
	post: {
		group: 'posts',
		label: __('Post', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) => {
			const url = `${REST_BASE}/wp/v2/posts?page=${page}&per_page=100&_fields=id,title`
			return fetchCached(url, () =>
				api.get<PostsSchema>(url).then(posts =>
					posts.map(post =>
						({ value: post.id, label: post.title.rendered }))))
		},
		fetchSearchOptions: (api, query) => {
			const url = `${REST_BASE}/wp/v2/posts?search=${encodeURIComponent(query)}&per_page=100&_fields=id,title`
			return fetchCached(url, () =>
				api.get<PostsSchema>(url).then(posts =>
					posts.map(post =>
						({ value: post.id, label: post.title.rendered }))))
		},
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: (() => {
					const url = `${REST_BASE}/wp/v2/posts?include=${ids.join(',')}&per_page=100&_fields=id,title`
					return fetchCached(url, () =>
						api.get<PostsSchema>(url).then(posts =>
							posts.map(post =>
								({ value: post.id, label: post.title.rendered }))))
				})()
		}
	},
	page: {
		group: 'posts',
		label: __('Page', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) =>
			fetchPageOptions(api, `${REST_BASE}/wp/v2/pages?page=${page}&per_page=100&_fields=id,title`),
		fetchSearchOptions: (api, query) =>
			fetchPageOptions(api, `${REST_BASE}/wp/v2/pages?search=${encodeURIComponent(query)}&per_page=100&_fields=id,title`),
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: fetchPageOptions(api, `${REST_BASE}/wp/v2/pages?include=${ids.join(',')}&per_page=100&_fields=id,title`)
		}
	},
	postType: {
		group: 'posts',
		label: __('Post type', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchAllOptions: api => {
			const url = `${REST_BASE}/wp/v2/types`
			return fetchCached(url, () =>
				api.get<PostTypesSchema>(url).then(postTypes =>
					Object.values(postTypes).map(postType =>
						({ value: postType.slug, label: postType.name }))))
		}
	},
	category: {
		group: 'posts',
		label: __('Post category', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) => {
			const url = `${REST_BASE}/wp/v2/categories?page=${page}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<Categories>(url).then(categories =>
					categories.map(category =>
						({ value: category.id, label: category.name }))))
		},
		fetchSearchOptions: (api, query) => {
			const url = `${REST_BASE}/wp/v2/categories?search=${encodeURIComponent(query)}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<Categories>(url).then(categories =>
					categories.map(category =>
						({ value: category.id, label: category.name }))))
		},
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: (() => {
					const url = `${REST_BASE}/wp/v2/categories?include=${ids.join(',')}&per_page=100&_fields=id,name`
					return fetchCached(url, () =>
						api.get<Categories>(url).then(categories =>
							categories.map(category =>
								({ value: category.id, label: category.name }))))
				})()
		}
	},
	tag: {
		group: 'posts',
		label: __('Post tag', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) => {
			const url = `${REST_BASE}/wp/v2/tags?page=${page}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<PostTags>(url).then(tags =>
					tags.map(tag =>
						({ value: tag.id, label: tag.name }))))
		},
		fetchSearchOptions: (api, query) => {
			const url = `${REST_BASE}/wp/v2/tags?search=${encodeURIComponent(query)}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<PostTags>(url).then(tags =>
					tags.map(tag =>
						({ value: tag.id, label: tag.name }))))
		},
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: (() => {
					const url = `${REST_BASE}/wp/v2/tags?include=${ids.join(',')}&per_page=100&_fields=id,name`
					return fetchCached(url, () =>
						api.get<PostTags>(url).then(tags =>
							tags.map(tag =>
								({ value: tag.id, label: tag.name }))))
				})()
		}
	},
	author: {
		group: 'posts',
		label: __('Post author', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) => {
			const url =
				`${REST_BASE}/wp/v2/users?who=authors&has_published_posts=true&page=${page}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<UsersSchema>(url).then(users =>
					users.map(user => ({ value: user.id, label: user.name }))))
		},
		fetchSearchOptions: (api, query) => {
			const url =
				`${REST_BASE}/wp/v2/users?who=authors&has_published_posts=true` +
				`&search=${encodeURIComponent(query)}&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<UsersSchema>(url).then(users =>
					users.map(user => ({ value: user.id, label: user.name }))))
		},
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: (() => {
					const url = `${REST_BASE}/wp/v2/users?include=${ids.join(',')}&per_page=100&_fields=id,name`
					return fetchCached(url, () =>
						api.get<UsersSchema>(url).then(users =>
							users.map(user => ({ value: user.id, label: user.name }))))
				})()
		}
	},
	postStatus: {
		group: 'posts',
		label: __('Post status', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchAllOptions: api => {
			const url = `${REST_BASE}/wp/v2/statuses`
			return fetchCached(url, () =>
				api.get<PostStatusesSchema>(url).then(statuses =>
					Object.values(statuses).map(status =>
						({ value: status.slug, label: status.name }))))
		}
	},
	postPublished: {
		group: 'posts',
		label: __('Publication date', 'code-snippets'),
		operators: CONDITION_OPERATORS.date
	},
	postModified: {
		group: 'posts',
		label: __('Modification date', 'code-snippets'),
		operators: CONDITION_OPERATORS.date
	}
}

const USER_CONDITION_SUBJECTS: ConditionSubjectDefinitions<UserConditionSubjects> = {
	authenticated: {
		group: 'users',
		label: __('Logged-in', 'code-snippets'),
		operators: CONDITION_OPERATORS.single,
		options: YES_NO_OPTIONS
	},
	user: {
		group: 'users',
		label: __('Current user', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchPagedOptions: (api, page) => {
			const url = `${REST_BASE}/wp/v2/users?page=${page}&orderby=id&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<UsersSchema>(url).then(users => [{
					label: __('User ID', 'code-snippets'),
					options: users.map(user => ({ value: user.id, label: `${user.id} (${user.name})` }))
				}]))
		},
		fetchSearchOptions: (api, query) => {
			const url = `${REST_BASE}/wp/v2/users?search=${encodeURIComponent(query)}&orderby=id&per_page=100&_fields=id,name`
			return fetchCached(url, () =>
				api.get<UsersSchema>(url).then(users => [{
					label: __('User ID', 'code-snippets'),
					options: users.map(user => ({ value: user.id, label: `${user.id} (${user.name})` }))
				}]))
		},
		fetchSelectedOptions: (api, values) => {
			const ids = values.filter((value): value is number => 'number' === typeof value)
			return 0 === ids.length
				? Promise.resolve([])
				: (() => {
					const url = `${REST_BASE}/wp/v2/users?include=${ids.join(',')}&per_page=100&_fields=id,name`
					return fetchCached(url, () =>
						api.get<UsersSchema>(url).then(users => [{
							label: __('User ID', 'code-snippets'),
							options: users.map(user => ({ value: user.id, label: `${user.id} (${user.name})` }))
						}]))
				})()
		}
	},
	userRole: {
		group: 'users',
		label: __('User role', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchAllOptions: api => {
			const url = `${REST_CONDITIONS_BASE}/roles`
			return fetchCached(url, () =>
				api.get<UserRoles>(url).then(roles =>
					roles.map(role =>
						({ value: role.role, label: role.name }))))
		}
	},
	userCap: {
		group: 'users',
		label: __('User capability', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchAllOptions: api => {
			const url = `${REST_CONDITIONS_BASE}/capabilities`
			return fetchCached(url, () =>
				api.get<string[]>(url).then(caps =>
					caps.map(cap =>
						({ value: cap, label: cap }))))
		}
	},
	userLocale: {
		group: 'users',
		label: __('User locale', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		fetchAllOptions: api => {
			const url = `${REST_CONDITIONS_BASE}/locales`
			return fetchCached(url, () =>
				api.get<LocalesSchema>(url).then(locales =>
					locales.map(locale =>
						({ value: locale.locale, label: locale.name }))))
		}
	},
	userRegistered: {
		group: 'users',
		label: __('Registration date', 'code-snippets'),
		operators: CONDITION_OPERATORS.date
	}
}

export const DATE_CONDITION_SUBJECTS: ConditionSubjectDefinitions<DateConditionSubjects> = {
	currentDate: {
		group: 'date',
		label: __('Current date', 'code-snippets'),
		operators: CONDITION_OPERATORS.date
	},
	timeOfDay: {
		group: 'date',
		label: __('Time of day', 'code-snippets'),
		operators: CONDITION_OPERATORS.date
	},
	dayOfWeek: {
		group: 'date',
		label: __('Day of week', 'code-snippets'),
		operators: CONDITION_OPERATORS.multiple,
		options: [
			{ value: 'Mon', label: __('Monday', 'code-snippets') },
			{ value: 'Tue', label: __('Tuesday', 'code-snippets') },
			{ value: 'Wed', label: __('Wednesday', 'code-snippets') },
			{ value: 'Thu', label: __('Thursday', 'code-snippets') },
			{ value: 'Fri', label: __('Friday', 'code-snippets') },
			{ value: 'Sat', label: __('Saturday', 'code-snippets') },
			{ value: 'Sun', label: __('Sunday', 'code-snippets') }
		]
	}
}

export const CONDITION_SUBJECTS: ConditionSubjectDefinitions<ConditionSubjects> = {
	...SITE_CONDITION_SUBJECTS,
	...POSTS_CONDITION_SUBJECTS,
	...USER_CONDITION_SUBJECTS,
	...DATE_CONDITION_SUBJECTS
}
