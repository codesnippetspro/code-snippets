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

// Tags implying a visual break become whitespace so `<p>A</p><p>B</p>`
// yields 'A B' rather than 'AB'; inline tags are removed without separators.
const BLOCK_TAGS = [
	'address', 'article', 'aside', 'blockquote', 'br', 'dd', 'div', 'dl', 'dt', 'fieldset',
	'figcaption', 'figure', 'footer', 'form', 'h[1-6]', 'header', 'hr', 'li', 'main', 'nav',
	'ol', 'p', 'pre', 'section', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul'
]

const BLOCK_TAG_PATTERN = new RegExp(`</?(?:${BLOCK_TAGS.join('|')})\\b[^>]*>`, 'gi')

export const stripTags = (text: string): string =>
	text
		.replace(/<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi, '')
		.replace(BLOCK_TAG_PATTERN, ' ')
		.replace(/<\/?[a-z][a-z0-9]*\b[^>]*>/gi, '')
		.replace(/\s+/g, ' ')
		.trim()
