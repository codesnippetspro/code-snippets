import type { PostSchema } from './PostSchema'

export interface PageSchema extends Omit<PostSchema, 'categories' | 'tags'> {
	parent: number
	menu_order: number
}

export type PagesSchema = PageSchema[]
