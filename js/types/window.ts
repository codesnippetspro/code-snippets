import { CodeEditorInstance, EditorOption, WordPressUtils } from './editor'

declare global {
	interface Window {
		pagenow: string
		ajaxurl: string
		wp: WordPressUtils
		code_snippets_tags: { allow_spaces: boolean, available_tags: string[] }
		code_snippets_editor?: CodeEditorInstance
		code_snippets_editor_preview?: CodeEditorInstance
		code_snippets_editor_settings: EditorOption[]
		code_snippets_edit_i18n: Record<string, string>
		code_snippets_manage_i18n: Record<string, string>
	}
}
