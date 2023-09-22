export const POSTS_ENDPOINT = '/wp/v2/posts'

export interface Post {
	id: number
	date: string | null
	date_gmt?: string | null
	readonly guid?: { rendered: string }
	link: string
	modified?: string
	modified_gmt?: string
	slug: string
	status?: PostStatus
	readonly type: string
	password?: string
	readonly permalink_template?: string
	readonly generated_slug?: string
	title: { rendered: string }
	content?: {
		rendered: string
		protected: boolean
	}
	excerpt: {
		rendered: string
		protected: false
	}
	author: number
	featured_media: number
	comment_status?: 'open' | 'closed'
	ping_status?: 'open' | 'closed'
	format?: PostFormat
	meta?: Record<string, unknown>
	sticky?: boolean
	template?: string
	categories?: number[]
	tags?: number[]
}

export type PostStatus = 'publish' | 'future' | 'draft' | 'pending' | 'private'

export type PostFormat =
	'standard' | 'aside' | 'chat' | 'gallery' | 'link' | 'image' | 'quote' | 'status' | 'video' | 'audio'

export type Posts = Post[]
