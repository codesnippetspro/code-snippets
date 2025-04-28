export interface PostStatus {
	name: string
	public: boolean
	queryable: boolean
	slug: string
	date_floating: boolean
	_links: Record<string, {
		href: string
	}[]>
}

export type PostStatuses = Record<string, PostStatus>
