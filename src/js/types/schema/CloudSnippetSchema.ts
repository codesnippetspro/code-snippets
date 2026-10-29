import type { SnippetScope } from '../Snippet'

export interface CloudSnippetSchema {
	id: number
	slug: string
	name: string
	description: string
	code: string
	tags: string[]
	scope: SnippetScope
	codevault: string
	total_votes: number
	vote_count: number
	wp_tested: string
	status: CloudStatus
	created: string
	updated: string
	revision: number
	is_owner: boolean
	local_id?: number | null
	update_available?: boolean | null
}

export enum CloudStatus {
	Private = 3,
	Public = 4,
	Unverified = 5,
	AI_Verified = 6,
	Pro_Verified = 8
}
