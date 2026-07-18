import { readFileSync } from 'fs'
import { join } from 'path'
import { expect, test } from '@playwright/test'

// WCAG AA contrast coverage for snippet type badges (12px bold = normal text,
// 4.5:1 minimum). Reads the palette straight from the SCSS theme so palette
// edits cannot silently regress accessibility; runs without a browser page.

const SNIPPET_TYPES = ['php', 'html', 'css', 'js', 'cond']
const AA_NORMAL_TEXT = 4.5
const DEFAULT_TEXT_COLOR = '#fff'

const themeScss = readFileSync(
	join(__dirname, '..', '..', 'src', 'css', 'common', '_theme.scss'),
	'utf8'
)

const scssVariable = (name: string): string => {
	const match = new RegExp(`^\\$${name}:\\s*(#[0-9a-fA-F]{3,8});`, 'm').exec(themeScss)
	if (!match) {
		throw new Error(`could not resolve $${name} in _theme.scss`)
	}
	return match[1]
}

const badgeColors = (name: string): [string, string] => {
	const badgesMap = /\$badges:\s*\((?<entries>[\s\S]*?)\);/.exec(themeScss)?.groups?.entries
	const entry = badgesMap
		? new RegExp(`^\\s*${name}:\\s*([^,\\n]+)`, 'm').exec(badgesMap)?.[1].trim()
		: undefined
	if (!entry) {
		throw new Error(`no $badges entry for ${name}`)
	}
	const [background, text = DEFAULT_TEXT_COLOR] = entry
		.split(/\s+/)
		.map(value => value.startsWith('$') ? scssVariable(value.slice(1)) : value)
	return [background, text]
}

const luminance = (hex: string): number => {
	const digits = hex.replace('#', '')
	const expanded = 3 === digits.length ? digits.replace(/./g, c => c + c) : digits
	const [r, g, b] = [0, 2, 4]
		.map(i => parseInt(expanded.slice(i, i + 2), 16) / 255)
		.map(v => 0.03928 >= v ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)
	return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

const contrastRatio = (a: string, b: string): number => {
	const [lighter, darker] = [luminance(a), luminance(b)].sort((x, y) => y - x)
	return (lighter + 0.05) / (darker + 0.05)
}

test.describe('badge contrast', () => {
	for (const type of SNIPPET_TYPES) {
		test(`${type} badge meets WCAG AA for normal text`, () => {
			const [background, text] = badgeColors(type)
			expect(contrastRatio(background, text)).toBeGreaterThanOrEqual(AA_NORMAL_TEXT)
		})
	}
})
