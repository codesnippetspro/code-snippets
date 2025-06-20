import type { PostStatusSchema } from './schema/PostStatusSchema'
import type { PageSchema } from './schema/PageSchema'
import type { PostSchema } from './schema/PostSchema'
import type { PostTypeSchema } from './schema/PostTypeSchema'
import type { CategorySchema, PostTagSchema } from './schema/TermSchema'
import type { UserSchema } from './schema/UserSchema'
import type { UserRole } from './schema/UserRole'
import type { Snippet } from './Snippet'

export interface SiteConditionSubjects {
	siteArea: 'global' | 'frontend' | 'admin'
	currentQuery: 'home' | 'frontpage' | 'search' | 'archive' | '404' | 'single' | 'page' | 'postTypeArchive'
	visitorLanguage: string
	currentTheme: string
	activePlugin: string
	debugEnabled: boolean
}

export interface SnippetConditionSubjects {
	condition: Snippet['id']
}

export interface PostConditionSubjects {
	post: PostSchema['id']
	page: PageSchema['id']
	postType: PostTypeSchema['slug']
	category: CategorySchema['id']
	tag: PostTagSchema['id']
	author: PostSchema['author']
	postStatus: PostStatusSchema['slug']
	postPublished: string
	postModified: string
}

export interface UserConditionSubjects {
	authenticated: boolean
	user: UserSchema['id']
	userRole: UserRole['role']
	userCap: string
	userLocale: string
	userRegistered: string
}

export interface DateConditionSubjects {
	currentDate: string
	timeOfDay: string
	dayOfWeek: 'Mon' | 'Tue' | 'Wed' | 'Thu' | 'Fri' | 'Sat' | 'Sun'
}

export type ConditionSubjects =
	SiteConditionSubjects &
	PostConditionSubjects &
	UserConditionSubjects &
	DateConditionSubjects

export type ConditionSubject = keyof ConditionSubjects
