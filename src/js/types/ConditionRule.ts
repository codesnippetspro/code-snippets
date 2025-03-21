import type { Page } from './wp/Page'
import type { Post } from './wp/Post'
import type { PostType } from './wp/PostType'
import type { Category, PostTag } from './wp/Term'
import type { User } from './wp/User'

export interface ConditionRule {
	enabled?: boolean
	subject?: ConditionSubject
	operator?: ConditionOperator
	object?: string | number | boolean
}

export interface ConditionSubjects {
	post: Post
	page: Page
	postType: PostType
	category: Category
	tag: PostTag
	user: User
	authenticated: boolean
	userRole: string
}

export type ConditionSubject = keyof ConditionSubjects

export type ConditionRules = Record<string, ConditionRule>

export type ConditionOperator = 'is' | 'not'
