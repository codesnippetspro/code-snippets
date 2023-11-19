export type ShortcodeAtts = Record<string, unknown>

export const buildShortcodeTag = (tag: string, atts: ShortcodeAtts): string =>
	`[${[
		tag,
		...Object.entries(atts)
			.filter(([, value]) => Boolean(value))
			.map(([att, value]) =>
				'boolean' === typeof value ? att : `${att}=${JSON.stringify(value)}`)
	].filter(Boolean).join(' ')}]`
