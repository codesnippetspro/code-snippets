import { autocompletion, closeBrackets } from '@codemirror/autocomplete'
import { history } from '@codemirror/commands'
import { bracketMatching, codeFolding, foldGutter, indentOnInput, indentUnit } from '@codemirror/language'
import { highlightSelectionMatches, search } from '@codemirror/search'
import { Annotation, Compartment, EditorState } from '@codemirror/state'
import {
	EditorView,
	drawSelection,
	dropCursor,
	highlightActiveLineGutter,
	highlightSpecialChars,
	highlightTrailingWhitespace,
	lineNumbers,
	rectangularSelection
} from '@codemirror/view'
import { indentationMarkers } from '@replit/codemirror-indentation-markers'
import { applyFilters } from '@wordpress/hooks'
import { snippetCompletions } from './completions'
import { keymapExtension, loadVimKeymap, saveKeymap } from './keymaps'
import { languageForType, lintForType } from './languages'
import { activeLineBackground, hasDarkBackground, themeClass, themeHighlighting } from './theme'
import type { Extension } from '@codemirror/state'
import type { ViewUpdate } from '@codemirror/view'
import type { EditorSettings } from '../types/EditorSettings'
import type { SnippetCodeType } from '../types/Snippet'

export type EditorSurface = 'edit' | 'preview' | 'settings'

/**
 * Passed to the `code_snippets.editor.extensions` filter so that callbacks can
 * decide which extensions apply.
 */
export interface EditorContext {
	snippetType: SnippetCodeType
	surface: EditorSurface
	isReadOnly: boolean
}

/**
 * Filter for adding CodeMirror extensions to every code editor the plugin
 * creates. Callbacks receive the current extension array and an
 * `EditorContext`, and are run again whenever the snippet type changes.
 *
 * Extensions must come from the same copy of `@codemirror/state` that the
 * plugin bundles, so this is only usable from code built alongside it.
 */
export const EXTENSIONS_FILTER = 'code_snippets.editor.extensions'

export interface CreateSnippetEditorOptions {
	parent: Element
	doc: string
	snippetType: SnippetCodeType
	settings: EditorSettings
	surface: EditorSurface
	/** Translations for the editor's own interface, keyed by English phrase. */
	phrases: Record<string, string>
	contentAttributes?: Record<string, string>
	readOnly?: boolean
	onChange?: (update: ViewUpdate) => void
	onSave?: VoidFunction
}

interface EditorConfigState {
	context: EditorContext
	settings: EditorSettings
	vim?: Extension
}

const DEFAULT_TAB_SIZE = 4

/**
 * Guide colours for light and dark themes, strong enough to see against a
 * theme's background without competing with the code.
 */
const INDENTATION_MARKER_COLORS = {
	light: '#dcdcde',
	activeLight: '#a7aaad',
	dark: '#4a4e57',
	activeDark: '#7b808a'
}

const compartments = {
	language: new Compartment(),
	lint: new Compartment(),
	settings: new Compartment(),
	keymap: new Compartment(),
	theme: new Compartment(),
	darkTheme: new Compartment(),
	direction: new Compartment(),
	registered: new Compartment()
}

const editorConfigs = new WeakMap<EditorView, EditorConfigState>()

/**
 * Marks changes the editor did not originate, such as a revision being
 * restored, so that they are not reported back as user edits.
 */
export const externalChange = Annotation.define<boolean>()

const settingsExtensions = (settings: EditorSettings): Extension => [
	EditorState.tabSize.of(0 < settings.tabSize ? settings.tabSize : DEFAULT_TAB_SIZE),
	indentUnit.of(settings.indentWithTabs ? '\t' : ' '.repeat(Math.max(1, settings.indentUnit))),
	settings.lineNumbers ? lineNumbers() : [],
	settings.foldGutter ? [codeFolding({ placeholderText: '…' }), foldGutter()] : [],
	settings.lineWrapping ? EditorView.lineWrapping : [],
	settings.autoCloseBrackets ? closeBrackets() : [],
	settings.highlightSelectionMatches ? highlightSelectionMatches() : [],
	settings.styleActiveLine ? [activeLineBackground, highlightActiveLineGutter()] : [],
	settings.indentationMarkers ? indentationMarkers({ colors: INDENTATION_MARKER_COLORS }) : [],
	settings.highlightTrailingWhitespace ? highlightTrailingWhitespace() : [],
	settings.matchBrackets ? bracketMatching() : []
]

const lintExtensions = ({ context, settings }: EditorConfigState): Extension =>
	settings.lint && !context.isReadOnly ? lintForType(context.snippetType) : []

const registeredExtensions = (context: EditorContext): Extension =>
	<Extension[]> applyFilters(EXTENSIONS_FILTER, [], context)

const directionExtension = (direction: EditorSettings['direction']): Extension =>
	EditorView.contentAttributes.of({ dir: direction })

const updateDarkTheme = (view: EditorView) => {
	view.dispatch({ effects: compartments.darkTheme.reconfigure(EditorView.darkTheme.of(false)) })
	view.dispatch({
		effects: compartments.darkTheme.reconfigure(EditorView.darkTheme.of(hasDarkBackground(view.dom)))
	})
}

const loadVim = (view: EditorView, config: EditorConfigState) => {
	loadVimKeymap()
		.then(vim => {
			config.vim = vim

			if (view.dom.isConnected && 'vim' === config.settings.keyMap) {
				view.dispatch({
					effects: compartments.keymap.reconfigure(keymapExtension('vim', !config.context.isReadOnly, vim))
				})
			}
		})
		.catch((error: unknown) => console.error('Could not load the Vim keymap.', error))
}

const editorExtensions = (
	{ phrases, contentAttributes = {}, onChange, onSave }: CreateSnippetEditorOptions,
	config: EditorConfigState
): Extension => {
	const { settings, context } = config

	return [
		onSave ? saveKeymap(onSave) : [],
		compartments.keymap.of(keymapExtension(settings.keyMap, !context.isReadOnly)),
		highlightSpecialChars(),
		history(),
		drawSelection(),
		dropCursor(),
		rectangularSelection(),
		EditorState.allowMultipleSelections.of(true),
		EditorState.readOnly.of(context.isReadOnly),
		EditorState.phrases.of(phrases),
		indentOnInput(),
		search({ top: true }),
		context.isReadOnly ? [] : [autocompletion(), snippetCompletions],
		themeHighlighting,
		EditorView.editorAttributes.of({ class: 'cs-code-editor' }),
		EditorView.contentAttributes.of(contentAttributes),
		compartments.theme.of(themeClass(settings.theme)),
		compartments.darkTheme.of([]),
		compartments.direction.of(directionExtension(settings.direction)),
		compartments.language.of(languageForType(context.snippetType)),
		compartments.lint.of(lintExtensions(config)),
		compartments.settings.of(settingsExtensions(settings)),
		compartments.registered.of(registeredExtensions(context)),
		EditorView.updateListener.of(update => {
			if (onChange && update.docChanged &&
				!update.transactions.every(transaction => transaction.annotation(externalChange))) {
				onChange(update)
			}
		})
	]
}

/**
 * Create a code editor for snippet code.
 */
export const createSnippetEditor = (options: CreateSnippetEditorOptions): EditorView => {
	const { parent, doc, snippetType, settings, surface, readOnly = false } = options
	const config: EditorConfigState = { context: { snippetType, surface, isReadOnly: readOnly }, settings }

	const view = new EditorView({
		parent,
		state: EditorState.create({ doc, extensions: editorExtensions(options, config) })
	})

	editorConfigs.set(view, config)
	updateDarkTheme(view)

	if ('vim' === settings.keyMap) {
		loadVim(view, config)
	}

	return view
}

/**
 * Switch the language, linting and registered extensions to a new snippet type.
 */
export const setEditorSnippetType = (view: EditorView, snippetType: SnippetCodeType) => {
	const config = editorConfigs.get(view)

	if (!config || config.context.snippetType === snippetType) {
		return
	}

	config.context = { ...config.context, snippetType }

	view.dispatch({
		effects: [
			compartments.language.reconfigure(languageForType(snippetType)),
			compartments.lint.reconfigure(lintExtensions(config)),
			compartments.registered.reconfigure(registeredExtensions(config.context))
		]
	})
}

/**
 * Apply changed editor settings, as the settings page preview does.
 */
export const setEditorSettings = (view: EditorView, settings: EditorSettings) => {
	const config = editorConfigs.get(view)

	if (!config) {
		return
	}

	const previous = config.settings
	config.settings = settings

	view.dispatch({
		effects: [
			compartments.settings.reconfigure(settingsExtensions(settings)),
			compartments.lint.reconfigure(lintExtensions(config)),
			compartments.direction.reconfigure(directionExtension(settings.direction)),
			compartments.theme.reconfigure(themeClass(settings.theme)),
			compartments.keymap.reconfigure(keymapExtension(settings.keyMap, !config.context.isReadOnly, config.vim))
		]
	})

	if (previous.theme !== settings.theme) {
		updateDarkTheme(view)
	}

	if ('vim' === settings.keyMap && !config.vim) {
		loadVim(view, config)
	}
}

export const setEditorDirection = (view: EditorView, direction: EditorSettings['direction']) => {
	const config = editorConfigs.get(view)

	if (config) {
		config.settings = { ...config.settings, direction }
	}

	view.dispatch({ effects: compartments.direction.reconfigure(directionExtension(direction)) })
}

/**
 * Replace the whole document, unless it already holds the given code or the
 * editor has not been created.
 */
export const replaceEditorContent = (view: EditorView | undefined, code: string) => {
	if (view && view.state.doc.toString() !== code) {
		view.dispatch({
			changes: { from: 0, to: view.state.doc.length, insert: code },
			annotations: externalChange.of(true)
		})
	}
}
