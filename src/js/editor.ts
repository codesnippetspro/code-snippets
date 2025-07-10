import { defineMode, getMode, registerHelper } from 'codemirror'
import { Linter } from './utils/Linter'
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
