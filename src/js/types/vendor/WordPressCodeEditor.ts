import type { EditorConfiguration, EditorFromTextArea } from 'codemirror'

export interface EditorOption {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: keyof EditorConfiguration
}

export interface CodeEditorInstance {
	codemirror: EditorFromTextArea
	settings: CodeEditorSettings
}

export interface CodeEditorSettings {
	codemirror: EditorConfiguration
	csslint: Record<string, unknown>
	htmlhint: Record<string, unknown>
	jshint: Record<string, unknown>
	onTabNext: VoidFunction
	onTabPrevious: VoidFunction
	onChangeLintingErrors: VoidFunction
	onUpdateErrorNotice: VoidFunction
}

export interface WordPressCodeEditor {
	initialize: (textarea: Element, options?: Partial<CodeEditorSettings>) => CodeEditorInstance
	defaultSettings: CodeEditorSettings
}
