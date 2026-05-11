import type { SnippetScope } from '../Snippet'

export interface CloudSnippetSchema {
	id: number
	cloud_id?: string
	name: string
	description: string
	code: string
	tags: string[]
	scope: SnippetScope
	language: string
	status: CloudStatus
	codevault: string
	total_votes: number
	vote_count: number
	wp_tested: string
	created: string
	updated: string
	revision: number
	is_owner: boolean
	shared_network: boolean
}

export enum CloudStatus {
	Private = 3,
	Public = 4,
	Unverified = 5,
	AI_Verified = 6,
	Pro_Verified = 8
}
