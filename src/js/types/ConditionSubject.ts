import type { Page } from './api/Page'
import type { Post } from './api/Post'
import type { PostType } from './api/PostType'
import type { Category, PostTag } from './api/Term'
import type { User } from './api/User'
import type { UserRole } from './api/UserRole'
import type { Snippet } from './Snippet'

export interface SiteConditionSubjects {
	siteArea: 'global' | 'frontend' | 'admin'
	currentQuery: 'frontpage' | 'home' | 'search' | 'archive' | '404'
	debugEnabled: boolean
}

export interface SnippetConditionSubjects {
	condition: Snippet['id']
}

export interface PostConditionSubjects {
	post: Post['id']
	page: Page['id']
	postType: PostType['slug']
	category: Category['id']
	tag: PostTag['id']
}

export interface UserConditionSubjects {
	user: User['id']
	authenticated: boolean
	userRole: UserRole['role']
	userCap: string
}

export type ConditionSubjects =
	SiteConditionSubjects &
	SnippetConditionSubjects &
	PostConditionSubjects &
	UserConditionSubjects

export type ConditionSubject = keyof ConditionSubjects
