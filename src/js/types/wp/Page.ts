import type { Post } from './Post'

export interface Page extends Omit<Post, 'categories' | 'tags'> {
	parent: number
	menu_order: number
}

export type Pages = Page[]
