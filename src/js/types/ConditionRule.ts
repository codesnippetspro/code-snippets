import type { Snippet } from './Snippet'
import type { UserRole } from './wp/UserRole'
import type { Page } from './wp/Page'
import type { Post } from './wp/Post'
import type { PostType } from './wp/PostType'
import type { Category, PostTag } from './wp/Term'
import type { User } from './wp/User'

export interface ConditionRule<S extends ConditionSubject> {
	readonly enabled?: boolean
	readonly subject?: S
	readonly operator?: ConditionOperator
	readonly object?: ConditionSubjects[ConditionSubject][]
}

export interface ConditionSubjects {
	global: boolean
	admin: boolean
	frontend: boolean
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

export type ConditionSubject = keyof ConditionSubjects

export type ConditionRules = Record<string, ConditionRule<ConditionSubject> | undefined>

export type ConditionOperator = 'is' | 'not'
