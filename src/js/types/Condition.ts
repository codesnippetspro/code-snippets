import type { Page } from './wp/Page'
import type { Post } from './wp/Post'
import type { PostType } from './wp/PostType'
import type { Category, PostTag } from './wp/Term'
import type { User } from './wp/User'

export interface Condition {
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

export type ConditionGroup = Record<string, Condition>
export type ConditionGroups = Record<string, ConditionGroup>

export type ConditionOperator = 'eq' | 'neq'
