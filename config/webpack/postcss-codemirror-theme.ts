import type { PluginCreator, Rule } from 'postcss'

/**
 * Selectors for CodeMirror 5 editor structure, and their CodeMirror 6
 * equivalents. Token classes such as `.cm-keyword` need no mapping, as the
 * editor's highlight style emits them unchanged.
 */
const SELECTOR_MAP: readonly (readonly [RegExp, string])[] = [
	[/\.CodeMirror-activeline-background(?![\w-])/g, '.cm-activeLine'],
	[/\.CodeMirror-activeline-gutter(?![\w-])/g, '.cm-activeLineGutter'],
	[/\.CodeMirror-gutters(?![\w-])/g, '.cm-gutters'],
	[/\.CodeMirror-gutter-elt(?![\w-])/g, '.cm-gutterElement'],
	[/\.CodeMirror-gutter(?![\w-])/g, '.cm-gutter'],
	[/\.CodeMirror-linenumber(?![\w-])/g, '.cm-lineNumbers .cm-gutterElement'],
	[/\.CodeMirror-guttermarker(?:-subtle)?(?![\w-])/g, '.cm-foldGutter .cm-gutterElement'],
	[/\.CodeMirror-foldmarker(?![\w-])/g, '.cm-foldPlaceholder'],
	[/\.CodeMirror-cursor(?![\w-])/g, '.cm-cursor'],
	[/\.CodeMirror-selected(?![\w-])/g, '.cm-selectionBackground'],
	[/\.CodeMirror-matchingbracket(?![\w-])/g, '.cm-matchingBracket'],
	[/\.CodeMirror-nonmatchingbracket(?![\w-])/g, '.cm-nonmatchingBracket'],
	[/\.CodeMirror-focused(?![\w-])/g, '.cm-focused'],
	[/\.CodeMirror-line(?![\w-])/g, '.cm-line'],
	[/\.CodeMirror-(?:code|lines)(?![\w-])/g, '.cm-content'],
	[/\.CodeMirror-scroll(?![\w-])/g, '.cm-scroller'],
	[/\.CodeMirror(?![\w-])/g, '.cm-editor'],
	[/\.cm-searching(?![\w-])/g, '.cm-searchMatch'],
	[/\.cm-matchhighlight(?![\w-])/g, '.cm-selectionMatch']
]

/**
 * Selectors with no CodeMirror 6 counterpart: native selection is hidden in
 * favour of drawn selection, and the rest style addons that are not loaded.
 */
const UNSUPPORTED_ADDON_CLASSES = [
	'simplescroll', 'overlayscroll', 'hint', 'ruler', 'widget', 'secondarycursor', 'overwrite', 'cursors',
	'matchingtag', 'selectedtext', 'foldgutter', 'gutter-text', 'activeline(?![\\w-])'
]

const UNSUPPORTED_SELECTOR = new RegExp(`::?-?(?:moz-)?selection|\\.CodeMirror-(?:${UNSUPPORTED_ADDON_CLASSES.join('|')})`)

const STRUCTURAL_CLASSES = [
	'editor', 'gutters?', 'gutterElement', 'lineNumbers', 'foldGutter', 'foldPlaceholder', 'cursor',
	'selectionBackground', 'matchingBracket', 'nonmatchingBracket', 'focused', 'line', 'content', 'scroller',
	'activeLine', 'activeLineGutter', 'searchMatch', 'selectionMatch'
]

const STRUCTURAL_SELECTOR = new RegExp(`\\.cm-(?:${STRUCTURAL_CLASSES.join('|')})(?![\\w-])`)

const convertSelector = (selector: string): string | null => {
	if (UNSUPPORTED_SELECTOR.test(selector)) {
		return null
	}

	const converted = SELECTOR_MAP.reduce((result, [pattern, replacement]) =>
		result.replace(pattern, replacement), selector)

	return converted.includes('.CodeMirror') ? null : converted
}

/**
 * Convert a CodeMirror 5 theme stylesheet to style a CodeMirror 6 editor.
 *
 * Declarations on editor structure are made important: CodeMirror 6 injects
 * its base theme after the page's stylesheets, so it would otherwise win any
 * rule of equal specificity.
 */
const postcssCodeMirrorTheme: PluginCreator<void> = () => ({
	postcssPlugin: 'postcss-codemirror-theme',
	Rule(rule: Rule) {
		if ('atrule' === rule.parent?.type && 'keyframes' === (<{ name?: string }> rule.parent).name) {
			return
		}

		const selectors = rule.selectors
			.map(convertSelector)
			.filter((selector): selector is string => null !== selector)

		if (0 === selectors.length) {
			rule.remove()
			return
		}

		rule.selectors = selectors

		if (selectors.some(selector => STRUCTURAL_SELECTOR.test(selector))) {
			rule.walkDecls(declaration => {
				declaration.important = true
			})
		}
	}
})

postcssCodeMirrorTheme.postcss = true

export default postcssCodeMirrorTheme
