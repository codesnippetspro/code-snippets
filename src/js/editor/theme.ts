import { cssLanguage } from '@codemirror/lang-css'
import { htmlLanguage } from '@codemirror/lang-html'
import { javascriptLanguage } from '@codemirror/lang-javascript'
import { phpLanguage } from '@codemirror/lang-php'
import { HighlightStyle, syntaxHighlighting } from '@codemirror/language'
import { EditorView, RectangleMarker, layer } from '@codemirror/view'
import { tags } from '@lezer/highlight'
import type { Language, TagStyle } from '@codemirror/language'
import type { Extension } from '@codemirror/state'
import type { Tag } from '@lezer/highlight'

type TokenClasses = readonly (readonly [Tag | readonly Tag[], string])[]

/**
 * Editor themes are CodeMirror 5 stylesheets, which colour tokens through
 * `.cm-s-{theme} .cm-{token}` rules. Emitting those same token classes lets
 * the stylesheets apply unchanged. Each grammar gets its own set because the
 * CodeMirror 5 modes classified the same construct differently per language,
 * and a tag matched by two sets would carry two competing classes.
 */
const SHARED_TOKENS: TokenClasses = [
	[tags.keyword, 'cm-keyword'],
	[tags.self, 'cm-keyword'],
	[[tags.atom, tags.bool, tags.null], 'cm-atom'],
	[[tags.number, tags.unit], 'cm-number'],
	[tags.comment, 'cm-comment'],
	[[tags.string, tags.attributeValue], 'cm-string'],
	[[tags.special(tags.string), tags.regexp, tags.escape], 'cm-string-2'],
	[[tags.definition(tags.variableName), tags.function(tags.definition(tags.variableName))], 'cm-def'],
	[tags.definition(tags.className), 'cm-def'],
	[[tags.propertyName, tags.function(tags.propertyName)], 'cm-property'],
	[[tags.typeName, tags.namespace], 'cm-variable-3 cm-type'],
	[tags.operator, 'cm-operator'],
	[[tags.processingInstruction, tags.meta, tags.documentMeta, tags.annotation], 'cm-meta'],
	[tags.tagName, 'cm-tag'],
	[tags.angleBracket, 'cm-tag cm-bracket'],
	[tags.attributeName, 'cm-attribute'],
	[tags.character, 'cm-atom'],
	[tags.standard(tags.variableName), 'cm-builtin'],
	[tags.heading, 'cm-header'],
	[[tags.link, tags.url], 'cm-link'],
	[tags.quote, 'cm-quote'],
	[tags.emphasis, 'cm-em'],
	[tags.strong, 'cm-strong'],
	[tags.strikethrough, 'cm-strikethrough'],
	[tags.invalid, 'cm-error']
]

const LANGUAGE_TOKENS: readonly (readonly [Language, TokenClasses])[] = [
	[phpLanguage, [
		[[tags.variableName, tags.special(tags.propertyName), tags.labelName], 'cm-variable-2'],
		[[tags.name, tags.function(tags.variableName)], 'cm-variable'],
		[tags.className, 'cm-variable-3 cm-type']
	]],
	[javascriptLanguage, [
		[[tags.variableName, tags.function(tags.variableName)], 'cm-variable'],
		[tags.special(tags.propertyName), 'cm-property'],
		[tags.labelName, 'cm-variable-2'],
		[tags.className, 'cm-variable-3 cm-type']
	]],
	[cssLanguage, [
		[tags.variableName, 'cm-variable-2'],
		[tags.className, 'cm-qualifier'],
		[tags.constant(tags.className), 'cm-variable-3'],
		[tags.labelName, 'cm-builtin'],
		[tags.color, 'cm-atom']
	]],
	[htmlLanguage, []]
]

const toTagStyles = (tokens: TokenClasses): TagStyle[] =>
	tokens.map(([tag, className]) => ({ tag, class: className }))

/**
 * Syntax highlighting that marks up tokens with CodeMirror 5 theme classes.
 */
export const themeHighlighting: Extension = LANGUAGE_TOKENS.map(([language, tokens]) =>
	syntaxHighlighting(HighlightStyle.define(toTagStyles([...SHARED_TOKENS, ...tokens]), { scope: language })))

/**
 * Highlight the line holding each cursor.
 *
 * Drawn on a layer behind the selection rather than as a line decoration:
 * theme stylesheets give the active line an opaque background, which would
 * otherwise cover the selection on that line. Lines holding a selection are
 * left alone, as they were in CodeMirror 5.
 */
export const activeLineBackground: Extension = layer({
	above: false,
	class: 'cm-activeLineLayer',
	update: update => update.docChanged || update.selectionSet || update.viewportChanged || update.geometryChanged,
	markers: view => {
		const scrollRect = view.scrollDOM.getBoundingClientRect()
		const contentRect = view.contentDOM.getBoundingClientRect()
		const left = (contentRect.left - scrollRect.left) / view.scaleX + view.scrollDOM.scrollLeft
		const documentOffset = (view.documentTop - scrollRect.top) / view.scaleY + view.scrollDOM.scrollTop
		const width = contentRect.width / view.scaleX

		const lineStarts = new Set(view.state.selection.ranges
			.filter(range => range.empty)
			.map(range => view.lineBlockAt(range.head).from))

		return [...lineStarts].map(from => {
			const block = view.lineBlockAt(from)
			return new RectangleMarker('cm-activeLine', left, documentOffset + block.top, width, block.height)
		})
	}
})

/**
 * Apply an editor theme by name. The theme's stylesheet is enqueued separately.
 */
export const themeClass = (theme: string): Extension =>
	EditorView.editorAttributes.of({ class: `cm-s-${theme || 'default'}` })

const RGB_PATTERN = /rgba?\(\s*(?<red>[\d.]+)[,\s]+(?<green>[\d.]+)[,\s]+(?<blue>[\d.]+)(?:[,\s/]+(?<alpha>[\d.]+%?))?/
const DARK_LUMINANCE_THRESHOLD = 0.4
const CHANNEL_MAX = 255

const LUMINANCE_WEIGHTS = <const> {
	red: 0.2126,
	green: 0.7152,
	blue: 0.0722
}

/**
 * Whether an element is painted with a dark background.
 *
 * Measured rather than listed so that themes added through the
 * `code_snippets_codemirror_atts` filter get a readable cursor and selection
 * too, not only the bundled ones.
 */
export const hasDarkBackground = (element: Element): boolean => {
	const channels = RGB_PATTERN.exec(getComputedStyle(element).backgroundColor)?.groups

	if (!channels || '0' === channels.alpha) {
		return false
	}

	const luminance = Object.entries(LUMINANCE_WEIGHTS)
		.reduce((sum, [channel, weight]) => sum + parseFloat(channels[channel]) / CHANNEL_MAX * weight, 0)

	return luminance < DARK_LUMINANCE_THRESHOLD
}
