import type { Snippet } from './Snippet'
import type { UserRole } from './api/UserRole'
import type { Page } from './api/Page'
import type { Post } from './api/Post'
import type { PostType } from './api/PostType'
import type { Category, PostTag } from './api/Term'
import type { User } from './api/User'

export type Condition = Record<string, ConditionGroup | undefined>
export type ConditionGroup = Record<string, ConditionRule<ConditionSubject> | undefined>
export type ConditionSubject = keyof ConditionSubjects

export interface ConditionRule<S extends ConditionSubject> {
	readonly subject?: S
	readonly operator?: ConditionOperator
	readonly object?: ConditionSubjects[S][]
}

export interface ConditionSubjects {
	siteArea: 'global' | 'frontend' | 'admin'
	post: Post['id']
	page: Page['id']
	postType: PostType['slug']
	category: Category['id']
	tag: PostTag['id']
	user: User['id']
	authenticated: boolean
	userRole: UserRole['role']
	userCap: string
	condition: Snippet['id']
}

export type ConditionOperator = 'is' | 'not'
