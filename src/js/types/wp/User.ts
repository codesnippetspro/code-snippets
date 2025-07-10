export const USERS_ENDPOINT = '/wp/v2/users'

export interface User {
	readonly id: number
	username?: string
	name: string
	first_name?: string
	last_name?: string
	email?: string
	url: string
	description: string
	readonly link: string
	locale?: string
	nickname?: string
	slug: string
	registered_date?: string
	roles?: string[]
	readonly capabilities?: Record<string, boolean>
	readonly extra_capabilities?: Record<string, boolean>
	readonly avatar_urls: Record<string, string>
	meta: Record<string, unknown>
}

export type Users = User[]
