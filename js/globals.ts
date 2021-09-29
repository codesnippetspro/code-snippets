import * as CodeMirror from 'codemirror';

export type EditorOption = {
	name: string
	type: 'checkbox' | 'number' | 'select'
	codemirror: any
};

export type CodeEditorInstance = {
	codemirror: CodeMirror.Editor
	settings: object
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
			codeEditor: any
			CodeMirror: typeof CodeMirror
		};
		code_snippets_edit_i18n: Record<string, string>;
	}
}
