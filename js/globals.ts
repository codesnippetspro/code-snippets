import * as CodeMirror from 'codemirror';

export type EditorOption = {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: keyof CodeMirror.EditorConfiguration
};

export type CodeEditorInstance = {
	codemirror: CodeMirror.Editor
	settings: Record<string, unknown>
};

export type CodeEditorSettings = {
	codemirror: CodeMirror.EditorConfiguration
};

export type WordPressUtils = {
	CodeMirror: typeof CodeMirror,
	codeEditor: {
		initialize: (textarea: Element, options?: CodeEditorSettings) => CodeEditorInstance
	}
};

declare global {
	interface Window {
		code_snippets_tags: { allow_spaces: boolean, available_tags: string[] };
		code_snippets_editor: CodeEditorInstance;
		code_snippets_editor_preview: CodeEditorInstance;
		code_snippets_editor_settings: EditorOption[];
		code_snippets_edit_i18n: Record<string, string>;
		code_snippets_manage_i18n: Record<string, string>;

		wp: WordPressUtils;
		pagenow: string;
		ajaxurl: string;
	}
}
