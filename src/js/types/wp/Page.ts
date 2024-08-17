import type { Post } from './Post'

export const PAGES_ENDPOINT = '/wp/v2/pages'

export interface Page extends Omit<Post, 'categories' | 'tags'> {
	parent: number
	menu_order: number
}

export type Pages = Page[]
