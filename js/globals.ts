import * as CodeMirror from 'codemirror';

export type EditorOption = {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: keyof CodeMirror.EditorConfiguration
};

export type CodeEditorInstance = {
	codemirror: CodeMirror.Editor
	settings: Record<string, unknown>
}

declare global {
	interface Window {
		code_snippets_editor: CodeEditorInstance;
		code_snippets_editor_preview: CodeEditorInstance;
		code_snippets_editor_settings: EditorOption[];
		code_snippets_tags: {
			allow_spaces: boolean
			available_tags: string[]
		};
		wp: {
			CodeMirror: typeof CodeMirror,
			codeEditor: {
				initialize: (textarea: Element, options?: CodeMirror.EditorConfiguration) => CodeEditorInstance
			}
		};
		code_snippets_edit_i18n: Record<string, string>;
		code_snippets_manage_i18n: Record<string, string>;
	}
}
