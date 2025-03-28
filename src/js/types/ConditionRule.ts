import type { Page } from './wp/Page'
import type { Post } from './wp/Post'
import type { PostType } from './wp/PostType'
import { UserRole } from './wp/UserRole'
import type { Category, PostTag } from './wp/Term'
import type { User } from './wp/User'

export interface ConditionRule {
	enabled?: boolean
	subject?: ConditionSubject
	operator?: ConditionOperator
	object?: string | number | boolean
}

export interface ConditionSubjects {
	global: boolean
	admin: boolean
	frontend: boolean
	post: Post
	page: Page
	postType: PostType
	category: Category
	tag: PostTag
	user: User
	authenticated: boolean
	userRole: UserRole
}

export type ConditionSubject = keyof ConditionSubjects

export type ConditionRules = Record<string, ConditionRule | undefined>

export type ConditionOperator = 'is' | 'not'
