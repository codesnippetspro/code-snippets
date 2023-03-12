import { CodeEditorInstance, EditorOption, WordPressUtils } from './editor'

declare global {
	interface Window {
		readonly pagenow: string
		readonly ajaxurl: string
		readonly wp: WordPressUtils
		code_snippets_editor?: CodeEditorInstance
		code_snippets_editor_preview?: CodeEditorInstance
		code_snippets_editor_settings: EditorOption[]
		readonly code_snippets_manage_i18n: Record<string, string>
		readonly CODE_SNIPPETS_EDIT?: {
			isPreview: boolean
			enableDownloads: boolean
			extraSaveButtons: boolean
			activateByDefault: boolean
			sharedNetworkSnippets: number[]
			enableDescription: boolean
			enableTags: boolean
			editorTheme: string
			tagOptions: {
				enabled: boolean
				allowSpaces: boolean
				availableTags: string[]
			}
		}
	}
}
