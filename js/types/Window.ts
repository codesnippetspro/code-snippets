import tinymce from 'tinymce'
import { Snippet } from './Snippet'
import { CodeEditorInstance, EditorOption, WordPressCodeEditor } from './WordPressCodeEditor'
import { WordPressEditor } from './WordPressEditor'

declare global {
	interface Window {
		readonly wp: {
			readonly editor?: WordPressEditor
			readonly codeEditor: WordPressCodeEditor
		}
		readonly pagenow: string
		readonly ajaxurl: string
		readonly tinymce?: tinymce.EditorManager
		readonly wpActiveEditor?: string
		code_snippets_editor_preview?: CodeEditorInstance
		readonly code_snippets_editor_settings: EditorOption[]
		readonly CODE_SNIPPETS?: {
			isLicensed: boolean
			restAPI: {
				base: string
				snippets: string
				cloud: string
				nonce: string
				localToken: string
			}
			urls: {
				plugin: string
				manage: string
				addNew: string
				edit: string
			}
		}
		readonly CODE_SNIPPETS_EDIT?: {
			snippet: Snippet
			isPreview: boolean
			enableTags: boolean
			enableDownloads: boolean
			scrollToNotices: boolean
			extraSaveButtons: boolean
			activateByDefault: boolean
			enableDescription: boolean
			editorTheme: string
			pageTitleActions: Record<string, string>
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
