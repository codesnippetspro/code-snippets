import tinymce from 'tinymce'

export interface VisualEditorSettings {
	tinymce: boolean | tinymce.Settings & {
		toolbar1?: string | string[]
		toolbar2?: string | string[]
		toolbar3?: string | string[]
		toolbar4?: string | string[]
	}
	quicktags: boolean | Record<string, string>
	mediaButtons: boolean
}

export interface WordPressEditor {
	initialize: (id: string, settings?: Partial<VisualEditorSettings>) => void
	remove: (id: string) => void
	getContent: (id: string) => string
}

export interface LocalisedEditor extends tinymce.Editor {
	getLang: (s: string) => string | Record<string, string>
}
