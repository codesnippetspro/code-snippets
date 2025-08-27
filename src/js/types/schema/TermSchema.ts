export interface TermSchema {
	id: number
	count: number
	description: string
	link: string
	name: string
	slug: string
	taxonomy: 'category' | 'post_tag' | 'nav_menu' | 'link_category' | 'post_format'
	meta: Record<string, unknown>
}

export type PostTagSchema = TermSchema

export interface CategorySchema extends TermSchema {
	parent: number
}

export type PostTags = PostTagSchema[]
export type Categories = CategorySchema[]
