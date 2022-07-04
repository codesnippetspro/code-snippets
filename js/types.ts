import { Editor, EditorConfiguration } from 'codemirror';
import * as Prism from 'prismjs';

export interface EditorOption {
	name: string;
	type: 'checkbox' | 'number' | 'select';
	codemirror: keyof EditorConfiguration;
}

export interface CodeEditorInstance {
	codemirror: Editor;
	settings: CodeEditorSettings;
}

export interface CodeEditorSettings {
	codemirror: EditorConfiguration;
	csslint: Record<string, unknown>;
	htmlhint: Record<string, unknown>;
	jshint: Record<string, unknown>;
	onTabNext: () => void;
	onTabPrevious: () => void;
	onChangeLintingErrors: () => void;
	onUpdateErrorNotice: () => void;
}

export interface WordPressUtils {
	codeEditor: {
		initialize: (textarea: Element, options?: Partial<CodeEditorSettings>) => CodeEditorInstance;
		defaultSettings: CodeEditorSettings;
	};
}

export interface ElementorFrontend {
	hooks: {
		addAction: (action: string, callback: (...args: unknown[]) => void, priority?: number, context?: unknown) => void
	}
}

export type SnippetType = 'css' | 'js' | 'php' | 'html';

export interface Snippet {
	id: number;
	name: string;
	scope: string;
	active: boolean;
	network: boolean;
	shared_network: boolean;
	priority: number;
	type: SnippetType;
}

export interface SnippetData {
	id: number
	name: string
	code: string
	active: boolean
	type: SnippetType
}

declare global {
	interface Window {
		pagenow: string;
		ajaxurl: string;
		wp: WordPressUtils;
		elementorFrontend: ElementorFrontend;
		code_snippets_tags: { allow_spaces: boolean, available_tags: string[] };
		code_snippets_editor?: CodeEditorInstance;
		code_snippets_editor_preview?: CodeEditorInstance;
		code_snippets_editor_settings: EditorOption[];
		code_snippets_edit_i18n: Record<string, string>;
		code_snippets_manage_i18n: Record<string, string>;
		code_snippets_prism?: typeof Prism;
	}
}
