import {Editor, EditorConfiguration} from 'codemirror';

export interface EditorOption {
	name: string;
	type: 'checkbox' | 'number' | 'select';
	codemirror: keyof EditorConfiguration;
}

export interface CodeEditorInstance {
	codemirror: Editor;
	settings: Record<string, unknown>;
}

export interface CodeEditorSettings {
	codemirror: EditorConfiguration;
}

export interface WordPressUtils {
	codeEditor: {
		initialize: (textarea: Element, options?: CodeEditorSettings) => CodeEditorInstance
	};
}

export type SnippetType = 'css' | 'js' | 'php' | 'html';

export interface Snippet {
	id?: number;
	name?: string;
	scope?: string;
	active?: boolean;
	network?: boolean;
	shared_network?: boolean;
	priority?: number;
	type?: SnippetType;
}

declare global {
	interface Window {
		pagenow: string;
		ajaxurl: string;
		wp: WordPressUtils;
		code_snippets_tags: { allow_spaces: boolean, available_tags: string[] };
		code_snippets_editor: CodeEditorInstance;
		code_snippets_editor_preview: CodeEditorInstance;
		code_snippets_editor_settings: EditorOption[];
		code_snippets_edit_i18n: Record<string, string>;
		code_snippets_manage_i18n: Record<string, string>;
	}
}
