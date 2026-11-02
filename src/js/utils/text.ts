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

export const stripTags = (text: string): string => {
	const document = new DOMParser().parseFromString(text, 'text/html')
	const blockSelector = 'p,div,li,br,h1,h2,h3,h4,h5,h6,tr,td,th,ul,ol,blockquote,table,pre,hr,' +
		'dl,dt,dd,section,article,header,footer,figure,figcaption,' +
		'address,aside,nav,main,fieldset,form,details,summary,dialog,hgroup,caption'

	document.body.querySelectorAll('script,style').forEach(element => element.remove())
	document.body
		.querySelectorAll(blockSelector)
		.forEach(element => element.after(document.createTextNode(' ')))

	return (document.body.textContent ?? '').replace(/\s+/g, ' ').trim()
}

export interface TextPart {
	text: string
	highlighted: boolean
}

const escapeRegExp = (text: string): string =>
	text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

/**
 * Split text into runs, marking those that match a word of the search term.
 */
export const splitHighlights = (text: string, term: string): TextPart[] => {
	// Longer words are tried first, so a word is not cut short by another that it begins with.
	const words = [...new Set(term.trim().split(/\s+/).filter(Boolean))]
		.sort((a, b) => b.length - a.length)

	if (!text || 0 === words.length) {
		return text ? [{ text, highlighted: false }] : []
	}

	const alternatives = words.map(escapeRegExp).join('|')
	const wholeMatch = new RegExp(`^(?:${alternatives})$`, 'iu')

	// Splitting on a capturing group keeps the matches in the list.
	return text.split(new RegExp(`(${alternatives})`, 'giu'))
		.filter(Boolean)
		.map(part => ({ text: part, highlighted: wholeMatch.test(part) }))
}
