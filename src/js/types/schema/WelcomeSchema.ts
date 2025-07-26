export type ChangelogSchema = {
	version: string
	date: string
	entries: ChangelogEntriesSchema
}

export type ChangelogEntriesSchema = {
	[section in ChangelogSectionTitle]?: {
		core?: string[]
		pro?: string[]
	}
}

export const CHANGELOG_SECTIONS = ['Added', 'Changed', 'Fixed', 'Deprecated', 'Removed', 'Security', 'Other'] as const
export type ChangelogSectionTitle = typeof CHANGELOG_SECTIONS[number]

export interface ImageLinkSchema {
	title: string
	image_url: string
	follow_url: string
	description?: string
	category?: string
}
