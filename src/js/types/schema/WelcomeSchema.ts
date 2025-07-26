export type ChangelogSchema = {
	[version in string]: ChangelogSectionSchema
}

export type ChangelogSectionSchema = {
	[section in ChangelogSectionTitle]?: {
		core?: string[]
		pro?: string[]
	}
}

export const CHANGELOG_SECTIONS = ['Added', 'Improved', 'Fixed', 'Other'] as const
export type ChangelogSectionTitle = typeof CHANGELOG_SECTIONS[number]

export interface ImageLinkSchema {
	title: string
	image_url: string
	follow_url: string
	description?: string
	category?: string
}
