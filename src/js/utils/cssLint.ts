import CodeMirror from 'codemirror'

interface CSSLintMessage {
	type: string
	line: number
	col: number
	message: string
	rule: { id: string }
}

interface CSSLintInstance {
	verify: (text: string, ruleset?: unknown) => { messages: CSSLintMessage[] }
}

export interface CssLintAnnotation {
	message: string
	severity: string
	from: CodeMirror.Position
	to: CodeMirror.Position
}

/**
 * CSSLint 1.0.5, the version WordPress ships, predates the fr unit and custom properties. Its parser stops at
 * '1fr' (in any letter case) and at '--name', and reports a parse error for valid CSS. Both are rewritten here, keeping every
 * character position, into a form the parser accepts: '1fr' becomes '1px', and '--name' becomes '-xname',
 * which CSSLint reads as a vendor-prefixed property and does not validate.
 */
export const maskModernCss = (code: string): string =>
	code
		.replace(/(?<digit>\d)fr\b/gi, '$<digit>px')
		.replace(/--(?=[A-Za-z0-9_-])/g, '-x')

/**
 * CSSLint cannot resolve var(), so it reports a property that uses one as having an invalid value.
 */
const isVarFalsePositive = (message: CSSLintMessage): boolean =>
	'known-properties' === message.rule.id && message.message.includes('var(')

export const lintCss = (cssLint: CSSLintInstance, text: string, options?: unknown): CssLintAnnotation[] =>
	cssLint.verify(maskModernCss(text), options).messages
		.filter(message => !isVarFalsePositive(message))
		.map(message => ({
			message: message.message,
			severity: message.type,
			from: CodeMirror.Pos(message.line - 1, message.col - 1),
			to: CodeMirror.Pos(message.line - 1, message.col)
		}))
