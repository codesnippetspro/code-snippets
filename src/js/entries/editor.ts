import CodeMirror, { defineMode, getMode, registerHelper } from 'codemirror'
import { Linter } from '../utils/Linter'
import type { EditorConfiguration, ModeSpec } from 'codemirror'

type LintHelper = (text: string, ...args: unknown[]) => unknown

interface ModeSpecOptions {
	startOpen: boolean
}

const mode: ModeSpec<ModeSpecOptions> = {
	name: 'application/x-httpd-php',
	startOpen: true
}

defineMode('php-snippet', (config: EditorConfiguration) => getMode(config, mode))

registerHelper('lint', 'php', (text: string) => {
	const linter = new Linter(text)
	linter.lint()

	return linter.annotations
})

const cssLint = (<{ lint?: Record<string, LintHelper> }> <unknown> CodeMirror).lint?.css

// CSSLint rejects the fr unit, so lint with px in its place. Both are two characters, which keeps marker positions.
if (cssLint) {
	registerHelper('lint', 'css', (text: string, ...args: unknown[]) =>
		cssLint(text.replace(/(?<digit>\d)fr\b/gi, '$1px'), ...args))
}
