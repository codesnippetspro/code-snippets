export interface PostType {
	description: string
	hierarchical: boolean
	has_archive: boolean
	name: string
	slug: string
	icon: string
	taxonomies: string[]
	rest_base: string
	rest_namespace: string
}

export type PostTypes = Record<string, PostType>
