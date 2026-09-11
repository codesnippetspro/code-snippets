/**
 * Code editor options, keyed by the names the editor settings fields declare
 * in their `codemirror` property and passed through the
 * `code_snippets_codemirror_atts` filter.
 */
export interface EditorSettings {
	indentWithTabs: boolean
	tabSize: number
	indentUnit: number
	lineWrapping: boolean
	foldGutter: boolean
	lineNumbers: boolean
	autoCloseBrackets: boolean
	highlightSelectionMatches: boolean
	styleActiveLine: boolean
	indentationMarkers: boolean
	highlightTrailingWhitespace: boolean
	matchBrackets: boolean
	lint: boolean
	keyMap: string
	theme: string
	direction: 'ltr' | 'rtl'
}

export interface EditorConfig {
	/** False when the current user has turned syntax highlighting off in their profile. */
	enabled: boolean
	settings: EditorSettings
}

export interface EditorOption {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: keyof EditorSettings | 'fontSize'
}
