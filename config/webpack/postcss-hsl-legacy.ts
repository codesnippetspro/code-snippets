interface HslMatch {
	fn: 'hsl' | 'hsla'
	h: string
	s: string
	l: string
	alpha?: string
}

interface DeclarationLike {
	value?: string
}

const GRAD_TO_DEG = 0.9
const DEG_PER_TURN = 360
const DEG_PER_PI = 180
const PERCENT_DIVISOR = 100
const ROUNDING_MULTIPLIER = 1000

const hslArgsRegex = new RegExp(
	[
		String.raw`(?<fn>hsl)a?\s*\(\s*`,
		String.raw`(?<hue>\d*\.?\d+(?:deg|grad|rad|turn)?)`,
		String.raw`(?:\s+|(?:\s*,\s*))`,
		String.raw`(?<s>\d*\.?\d+%)`,
		String.raw`(?:\s+|(?:\s*,\s*))`,
		String.raw`(?<l>\d*\.?\d+%)`,
		String.raw`(?:\s*(?:\/|,)\s*(?<alpha>\d*\.?\d+%?))?`,
		String.raw`\s*\)`
	].join(''),
	'g'
)

const hueWithUnitRegex = /^(?<value>\d*\.?\d+)(?<unit>deg|grad|rad|turn)$/

const convertHueToDeg = (hue: string): string => {
	const match = hueWithUnitRegex.exec(hue)
	if (!match?.groups) {
		return hue
	}

	const value = Number(match.groups.value)
	const unit = match.groups.unit

	const degrees =
		'deg' === unit
			? value
			: 'grad' === unit
				? value * GRAD_TO_DEG
				: 'rad' === unit
					? value * DEG_PER_PI / Math.PI
					: value * DEG_PER_TURN

	return String(Math.round(degrees * ROUNDING_MULTIPLIER) / ROUNDING_MULTIPLIER)
}

const normalizeAlpha = (alpha: string): string => {
	if (alpha.includes('%')) {
		const value = Number(alpha.slice(0, -1)) / PERCENT_DIVISOR
		alpha = String(value)
	}

	return alpha.replace(/^0\./, '.')
}

const toLegacyHsl = (colorFn: string): HslMatch | null => {
	hslArgsRegex.lastIndex = 0
	const match = hslArgsRegex.exec(colorFn)
	if (!match?.groups) {
		return null
	}

	const alpha = match.groups.alpha

	return {
		fn: alpha ? 'hsla' : 'hsl',
		h: convertHueToDeg(match.groups.hue),
		s: match.groups.s,
		l: match.groups.l,
		alpha: alpha ? normalizeAlpha(alpha) : undefined
	}
}

const isIdentChar = (char: string | undefined): boolean => Boolean(char && /[a-zA-Z0-9_-]/.test(char))

const isUnescapedQuote = (value: string, index: number, quote: '"' | "'"): boolean =>
	quote === value[index] && '\\' !== value[index - 1]

const findFunctionEnd = (value: string, openParenIndex: number): number | null => {
	let depth = 0
	let index = openParenIndex

	while (index < value.length) {
		const char = value[index]

		if ('(' === char) {
			depth += 1
		} else if (')' === char) {
			depth -= 1
			if (0 === depth) {
				return index
			}
		}

		index += 1
	}

	return null
}

const legacyHslString = (fnText: string): string | null => {
	const legacy = toLegacyHsl(fnText)
	if (!legacy) {
		return null
	}

	if ('hsl' === legacy.fn) {
		return `hsl(${legacy.h}, ${legacy.s}, ${legacy.l})`
	}

	return `hsla(${legacy.h}, ${legacy.s}, ${legacy.l}, ${legacy.alpha})`
}

const replaceHslAtIndex = (
	value: string,
	index: number
): { nextIndex: number; text: string } | null => {
	const isStart = value.startsWith('hsl', index) || value.startsWith('hsla', index)
	if (!isStart || isIdentChar(value[index - 1])) {
		return null
	}

	const name = value.startsWith('hsla', index) ? 'hsla' : 'hsl'
	let afterNameIndex = index + name.length

	while (afterNameIndex < value.length && /\s/.test(value[afterNameIndex])) {
		afterNameIndex += 1
	}

	if ('(' !== value[afterNameIndex]) {
		return null
	}

	const endIndex = findFunctionEnd(value, afterNameIndex)
	if (null === endIndex) {
		return { nextIndex: value.length, text: value.slice(index) }
	}

	const fnText = value.slice(index, endIndex + 1)
	return { nextIndex: endIndex + 1, text: legacyHslString(fnText) ?? fnText }
}

const transformHslFunctions = (value: string): string => {
	let result = ''
	let index = 0

	let inSingle = false
	let inDouble = false

	while (index < value.length) {
		const char = value[index]

		if (!inDouble && isUnescapedQuote(value, index, "'")) {
			inSingle = !inSingle
			result += char
			index += 1
			continue
		}

		if (!inSingle && isUnescapedQuote(value, index, '"')) {
			inDouble = !inDouble
			result += char
			index += 1
			continue
		}

		if (inSingle || inDouble) {
			result += char
			index += 1
			continue
		}

		const replacement = replaceHslAtIndex(value, index)
		if (!replacement) {
			result += char
			index += 1
			continue
		}

		result += replacement.text
		index = replacement.nextIndex
	}

	return result
}

const postcssHslLegacy = () => ({
	postcssPlugin: 'postcss-hsl-legacy',
	Declaration(decl: DeclarationLike) {
		if (!decl.value || !decl.value.includes('hsl(') && !decl.value.includes('hsla(')) {
			return
		}
		decl.value = transformHslFunctions(decl.value)
	}
})

postcssHslLegacy.postcss = true

export default postcssHslLegacy
