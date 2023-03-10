import { Editor, EditorConfiguration } from 'codemirror'

export interface EditorOption {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: keyof EditorConfiguration
}

export interface CodeEditorInstance {
	codemirror: Editor
	settings: CodeEditorSettings
}

export interface CodeEditorSettings {
	codemirror: EditorConfiguration
	csslint: Record<string, unknown>
	htmlhint: Record<string, unknown>
	jshint: Record<string, unknown>
	onTabNext: () => void
	onTabPrevious: () => void
	onChangeLintingErrors: () => void
	onUpdateErrorNotice: () => void
}

export interface WordPressUtils {
	codeEditor: {
		initialize: (textarea: Element, options?: Partial<CodeEditorSettings>) => CodeEditorInstance
		defaultSettings: CodeEditorSettings
	}
}
