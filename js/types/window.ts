import { CodeEditorInstance, EditorOption, WordPressUtils } from './editor'
import * as Prism from 'prismjs'

export interface ElementorFrontend {
	hooks: {
		addAction: (action: string, callback: (...args: unknown[]) => void, priority?: number, context?: unknown) => void
	}
}

declare global {
	interface Window {
		pagenow: string
		ajaxurl: string
		wp: WordPressUtils
		elementorFrontend: ElementorFrontend
		code_snippets_tags: { allow_spaces: boolean, available_tags: string[] }
		code_snippets_editor?: CodeEditorInstance
		code_snippets_editor_preview?: CodeEditorInstance
		code_snippets_editor_settings: EditorOption[]
		code_snippets_edit_i18n: Record<string, string>
		code_snippets_manage_i18n: Record<string, string>
		code_snippets_prism?: typeof Prism
	}
}
