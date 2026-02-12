export interface ChangelogSchema {
	version: string
	date: string
	entries: ChangelogEntriesSchema
}

export type ChangelogEntriesSchema = Partial<Record<ChangelogSectionTitle, { core?: string[], pro?: string[] }>>

export const CHANGELOG_SECTIONS = <const> ['Added', 'Changed', 'Fixed', 'Deprecated', 'Removed', 'Security', 'Other']
export type ChangelogSectionTitle = typeof CHANGELOG_SECTIONS[number]

export interface ImageLinkSchema {
	title: string
	image_url: string
	follow_url: string
	description?: string
	category?: string
}
