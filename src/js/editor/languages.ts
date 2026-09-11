import { css } from '@codemirror/lang-css'
import { javascript } from '@codemirror/lang-javascript'
import { php } from '@codemirror/lang-php'
import { ensureSyntaxTree, syntaxTree } from '@codemirror/language'
import { lintGutter, linter } from '@codemirror/lint'
import { Linter } from '../utils/Linter'
import type { Diagnostic } from '@codemirror/lint'
import type { Extension } from '@codemirror/state'
import type { EditorView } from '@codemirror/view'
import type { SnippetCodeType } from '../types/Snippet'

const PARSE_TIMEOUT_MS = 500

/**
 * Language support for each snippet type.
 *
 * PHP snippets are stored without an opening tag, so they are parsed as PHP
 * from the first character, with HTML only after a closing `?>`. Content
 * snippets are HTML templates that may contain PHP, with CSS and JavaScript
 * nested inside their `<style>` and `<script>` elements.
 */
export const languageForType = (type: SnippetCodeType): Extension => {
	switch (type) {
		case 'css':
			return css()
		case 'js':
			return javascript()
		case 'html':
			return php()
		case 'php':
			return php({ plain: true })
	}
}

const phpDiagnostics = (view: EditorView): Diagnostic[] => {
	const linter = new Linter(view.state.doc.toString())
	linter.lint()

	const length = view.state.doc.length

	return linter.annotations.map(({ message, severity, from, to }) => ({
		message,
		severity,
		from: Math.min(from, length),
		to: Math.min(to, length)
	}))
}

const syntaxErrorDiagnostics = (view: EditorView): Diagnostic[] => {
	const { state } = view
	const tree = ensureSyntaxTree(state, state.doc.length, PARSE_TIMEOUT_MS) ?? syntaxTree(state)
	const diagnostics: Diagnostic[] = []

	tree.iterate({
		enter: ({ type, from, to }) => {
			if (type.isError) {
				diagnostics.push({ from, to, severity: 'error', message: state.phrase('Syntax error') })
			}
		}
	})

	return diagnostics
}

/**
 * Linting for each snippet type. Content snippets are not linted, as a
 * fragment of a template is rarely valid markup on its own.
 */
export const lintForType = (type: SnippetCodeType): Extension => {
	switch (type) {
		case 'php':
			return [linter(phpDiagnostics), lintGutter()]
		case 'css':
		case 'js':
			return [linter(syntaxErrorDiagnostics), lintGutter()]
		case 'html':
			return []
	}
}
