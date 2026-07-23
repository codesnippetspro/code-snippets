import { _x } from '@wordpress/i18n'

const DEFAULT_MAX_CHARS = 150

export const toCamelCase = (text: string): string =>
	text.replace(/-(?<letter>[a-z])/g, (_, letter: string) => letter.toUpperCase())

export const trimLeadingChar = (text: string, characters: string): string =>
	characters.includes(text.charAt(0)) ? text.slice(1) : text

export const trimTrailingChar = (text: string, characters: string): string =>
	characters.includes(text.charAt(text.length - 1)) ? text.slice(0, -1) : text

export const truncateChars = (text: string, chars = DEFAULT_MAX_CHARS): string =>
	text.length > chars
		? `${text.slice(0, chars)}${_x('…', 'truncated text', 'code-snippets')}`
		: text

export const truncateWords = (text: string, wordCount: number): string => {
	const words = text.trim().split(/\s+/)

	return words.length > wordCount
		? `${words.slice(0, wordCount).join(' ')}${_x('…', 'truncated text', 'code-snippets')}`
		: text
}

const NAMED_ENTITIES: Record<string, string> = {
	amp: '&',
	apos: "'",
	copy: '©',
	gt: '>',
	hellip: '…',
	laquo: '«',
	ldquo: '“',
	lsquo: '‘',
	lt: '<',
	mdash: '—',
	nbsp: '\u00a0',
	ndash: '–',
	quot: '"',
	raquo: '»',
	rdquo: '”',
	reg: '®',
	rsquo: '’',
	trade: '™'
}

const DECIMAL_RADIX = 10
const HEX_RADIX = 16
const MAX_CODE_POINT = 0x10ffff
const SURROGATE_RANGE_START = 0xd800
const SURROGATE_RANGE_END = 0xdfff

// A single left-to-right pass never rescans replacement output, so
// double-encoded input such as `&amp;lt;` decodes to `&lt;` and not `<`.
export const decodeEntities = (text: string): string =>
	text.replace(
		/&(?:#(?<decimal>\d{1,7})|#x(?<hex>[\da-f]{1,6})|(?<named>[a-z]+));/gi,
		(match, decimal: string | undefined, hex: string | undefined, named: string | undefined) => {
			if (undefined !== named) {
				return NAMED_ENTITIES[named.toLowerCase()] ?? match
			}

			const code = parseInt(decimal ?? hex ?? '', undefined === decimal ? HEX_RADIX : DECIMAL_RADIX)

			return 0 < code && MAX_CODE_POINT >= code && (SURROGATE_RANGE_START > code || SURROGATE_RANGE_END < code)
				? String.fromCodePoint(code)
				: match
		}
	)

export const stripTags = (text: string): string => {
	const document = new DOMParser().parseFromString(text, 'text/html')

	document.body
		.querySelectorAll('p,div,li,br,h1,h2,h3,h4,h5,h6,tr,td')
		.forEach(element => element.after(document.createTextNode(' ')))

	return (document.body.textContent ?? '').replace(/\s+/g, ' ').trim()
}
