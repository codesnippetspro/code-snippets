import tinymce from 'tinymce'
import { CodeEditorInstance, EditorOption, WordPressCodeEditor } from './WordPressCodeEditor'
import { WordPressEditor } from './WordPressEditor'

export interface WordPressUtils {
	readonly wpActiveEditor?: string
	readonly tinymce?: typeof tinymce
	readonly editor?: WordPressEditor
	readonly codeEditor: WordPressCodeEditor
}

declare global {
	interface Window {
		readonly pagenow: string
		readonly ajaxurl: string
		readonly wp: WordPressUtils
		code_snippets_editor_preview?: CodeEditorInstance
		code_snippets_editor_settings: EditorOption[]
		readonly CODE_SNIPPETS_EDIT?: {
			isPreview: boolean
			enableDownloads: boolean
			extraSaveButtons: boolean
			activateByDefault: boolean
			enableDescription: boolean
			enableTags: boolean
			editorTheme: string
			tagOptions: {
				enabled: boolean
				allowSpaces: boolean
				availableTags: string[]
			}
			descEditorOptions: {
				rows: number
				mediaButtons: boolean
			}
		}
	}
}
