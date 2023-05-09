export type ShortcodeAtts = Record<string, unknown>

export const buildShortcodeTag = (tag: string, atts: ShortcodeAtts): string =>
	`[${[
		tag,
		...Object.entries(atts).map(([att, value]) =>
			value ? `${att}=${JSON.stringify(value)}` : '')
	].filter(Boolean).join(' ')}]`
