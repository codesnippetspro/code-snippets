export interface PostStatusSchema {
	name: string
	public: boolean
	queryable: boolean
	slug: string
	date_floating: boolean
	_links: Record<string, {
		href: string
	}[]>
}

export type PostStatusesSchema = Record<string, PostStatusSchema>
