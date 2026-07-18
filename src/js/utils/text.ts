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
const BLOCK_TAG_NAMES = new Set([
	'address', 'article', 'aside', 'blockquote', 'br', 'dd', 'div', 'dl', 'dt', 'fieldset',
	'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header',
	'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'tbody', 'td', 'tfoot',
	'th', 'thead', 'tr', 'ul'
])

const TAG_START = /<\/?(?<name>[a-z][a-z0-9]*)/iy

// Tag stripping is manual scanning rather than regexes because the regex
// equivalents rescanned to end of input at every failed tag-like start,
// going quadratic on repeated comparison text such as `a<b ... c<d ...`.
// The suffix memos below make every check constant-time after one linear
// reverse pass per input.

// memo[position] is the index of the first `>` at or after `position` that
// is not inside a quoted attribute value, or -1. Quote-aware so `>` inside
// quoted values, e.g. `<p title="1 > 0">`, does not end a tag early.
const buildUnquotedGtMemo = (text: string): number[] => {
	const memo = new Array<number>(text.length + 1)
	memo[text.length] = -1
	let nextDouble = -1
	let nextSingle = -1

	for (let position = text.length - 1; 0 <= position; position--) {
		const character = text.charAt(position)

		if ('>' === character) {
			memo[position] = position
		} else if ('"' === character) {
			memo[position] = -1 === nextDouble ? -1 : memo[nextDouble + 1]
			nextDouble = position
		} else if ("'" === character) {
			memo[position] = -1 === nextSingle ? -1 : memo[nextSingle + 1]
			nextSingle = position
		} else {
			memo[position] = memo[position + 1]
		}
	}

	return memo
}

// Memo[position - from] is true when a quote-state scan of text from
// `position` ends inside an unterminated quote. Used on the remnant after
// the last `>`: a tag-like start there is only stripped to end of input when
// it contains an unterminated quote, e.g. `<p title="broken`, so plain
// comparison text such as `x<y` is preserved.
const buildOpenQuoteMemo = (text: string, from: number): boolean[] => {
	const memo = new Array<boolean>(text.length + 1 - from)
	memo[text.length - from] = false
	let nextDouble = -1
	let nextSingle = -1

	for (let position = text.length - 1; position >= from; position--) {
		const character = text.charAt(position)

		if ('"' === character) {
			memo[position - from] = -1 === nextDouble || memo[nextDouble + 1 - from]
			nextDouble = position
		} else if ("'" === character) {
			memo[position - from] = -1 === nextSingle || memo[nextSingle + 1 - from]
			nextSingle = position
		} else {
			memo[position - from] = memo[position + 1 - from]
		}
	}

	return memo
}

// Comment and PHP removal is a manual scan for the same reason: the regex
// alternation rescanned to end of input at every unmatched `<!--` or `<?`
// opener, going quadratic on repeated unmatched openers. The lastIndexOf
// guards make unmatched openers constant-time, and closer searches for
// matched spans cover disjoint ranges, so the scan is linear.
const stripCommentsAndPhp = (text: string): string => {
	const lastCommentClose = text.lastIndexOf('-->')
	const lastPhpClose = text.lastIndexOf('?>')
	let result = ''
	let copied = 0
	let position = text.indexOf('<')

	while (-1 !== position) {
		let close = -1
		let closeLength = 0

		if (text.startsWith('<!--', position)) {
			close = lastCommentClose >= position + '<!--'.length ? text.indexOf('-->', position + '<!--'.length) : -1
			closeLength = '-->'.length
		} else if (text.startsWith('<?', position)) {
			close = lastPhpClose >= position + '<?'.length ? text.indexOf('?>', position + '<?'.length) : -1
			closeLength = '?>'.length
		}

		if (-1 === close) {
			position = text.indexOf('<', position + 1)
			continue
		}

		result += text.slice(copied, position)
		copied = close + closeLength
		position = text.indexOf('<', copied)
	}

	return 0 === copied ? text : result + text.slice(copied)
}

// One stripping pass. Quote-aware passes remove well-formed tags, ending at
// the first `>` outside quoted attribute values. Fallback passes then remove
// malformed remnants that never satisfy the quote-aware parse, stripping to
// the nearest `>` regardless of quotes, or to end of input when the remnant
// after the last `>` contains an unterminated quote.
const stripTagsPass = (text: string, tagNames: Set<string> | undefined, replacement: string, quoteAware: boolean): string => {
	const lastGt = text.lastIndexOf('>')
	let unquotedGtMemo: number[] | undefined
	let openQuoteMemo: boolean[] | undefined
	let result = ''
	let copied = 0
	let position = text.indexOf('<')

	while (-1 !== position) {
		TAG_START.lastIndex = position
		const name = TAG_START.exec(text)?.groups?.name
		const nameEnd = TAG_START.lastIndex

		if (!name || tagNames && !tagNames.has(name.toLowerCase()) || /\w/.test(text.charAt(nameEnd))) {
			position = text.indexOf('<', position + 1)
			continue
		}

		let end: number

		if (quoteAware) {
			unquotedGtMemo ??= buildUnquotedGtMemo(text)
			const unquotedGt = unquotedGtMemo[nameEnd]

			if (-1 === unquotedGt) {
				position = text.indexOf('<', position + 1)
				continue
			}

			end = unquotedGt + 1
		} else if (position <= lastGt) {
			end = text.indexOf('>', nameEnd) + 1
		} else {
			openQuoteMemo ??= buildOpenQuoteMemo(text, lastGt + 1)

			if (!openQuoteMemo[nameEnd - lastGt - 1]) {
				position = text.indexOf('<', nameEnd)
				continue
			}

			end = text.length
		}

		result += text.slice(copied, position) + replacement
		copied = end
		position = text.indexOf('<', end)
	}

	return 0 === copied ? text : result + text.slice(copied)
}

export const stripTags = (text: string): string => {
	const withoutComments = stripCommentsAndPhp(text)
	const withoutTags = stripTagsPass(stripTagsPass(withoutComments, BLOCK_TAG_NAMES, ' ', true), undefined, '', true)
	const withoutRemnants = stripTagsPass(stripTagsPass(withoutTags, BLOCK_TAG_NAMES, ' ', false), undefined, '', false)

	return withoutRemnants.replace(/\s+/g, ' ').trim()
}
