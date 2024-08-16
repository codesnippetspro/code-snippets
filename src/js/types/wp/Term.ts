export const TAGS_ENDPOINT = '/wp/v2/tags'
export const CATEGORIES_ENDPOINT = '/wp/v2/categories'

export interface Term {
	id: number
	count: number
	description: string
	link: string
	name: string
	slug: string
	taxonomy: Taxonomy
	meta: Record<string, unknown>
}

export type PostTag = Term

export interface Category extends Term {
	parent: number
}

export type Taxonomy = 'category' | 'post_tag' | 'nav_menu' | 'link_category' | 'post_format'

export type Terms = Term[]
export type PostTags = PostTag[]
export type Categories = Category[]
