export interface PostTypeSchema {
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

export type PostTypesSchema = Record<string, PostTypeSchema>
