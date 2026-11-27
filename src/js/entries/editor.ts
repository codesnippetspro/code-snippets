import { defineMode, getMode, registerHelper } from 'codemirror'
import { Linter } from '../utils/Linter'
import { lintCss } from '../utils/cssLint'
import type { EditorConfiguration, ModeSpec } from 'codemirror'

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

// Replaces the CSS helper from the WordPress CodeMirror bundle, which reports the fr unit and custom properties as errors.
registerHelper('lint', 'css', (text: string, options: unknown) =>
	'CSSLint' in window ? lintCss((<{ CSSLint: Parameters<typeof lintCss>[0] }><unknown>window).CSSLint, text, options) : [])
