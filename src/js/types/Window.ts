import type { SnippetSchema } from './schema/SnippetSchema'
import type { ChangelogSchema, ImageLinkSchema } from './schema/WelcomeSchema'
import type Prism from 'prismjs'
import type tinymce from 'tinymce'
import type { InsightsChartViews, InsightsSummary } from './Insights'
import type { Snippet } from './Snippet'
import type { SnippetView } from './SnippetView'
import type { CodeEditorInstance, EditorOption, WordPressCodeEditor } from './vendor/WordPressCodeEditor'
import type { WordPressEditor } from './vendor/WordPressEditor'

declare global {
	interface Window {
		readonly wp: {
			readonly editor?: WordPressEditor
			readonly codeEditor?: WordPressCodeEditor
		}
		readonly pagenow: string
		readonly ajaxurl: string
		readonly tinymce?: tinymce.EditorManager
		readonly wpActiveEditor?: string
		code_snippets_editor_preview?: CodeEditorInstance
		readonly code_snippets_editor_settings: EditorOption[]
		CODE_SNIPPETS_PRISM?: typeof Prism
		readonly CODE_SNIPPETS?: {
			debug: boolean
			isLicensed: boolean
			hideUpsell: boolean
			isCloudConnected: boolean
			snippetView: SnippetView
			insightsChartViews: InsightsChartViews
			restAPI: {
				base: string
				snippets: string
				recentlyActive: string
				preferences: string
				importPlugins: string
				importFiles: string
				nonce: string
				cloud: {
					token: string
					snippets: string
				}
			}
			urls: {
				plugin: string
				manage: string
				addNew: string
				edit: string
				insights: string
				welcome: string
				import: string
				settings: string
				cloud: string
			}
			banner: {
				key: string
				start_datetime: { date: string, timezone_type: number, timezone: string }
				end_datetime: { date: string, timezone_type: number, timezone: string }
				text_free: string
				action_url_free: string
				action_label_free: string
				text_pro: string
				action_url_pro: string
				action_label_pro: string
			}
		}
		readonly CODE_SNIPPETS_MANAGE?: {
			snippetsList?: Snippet[]
			hasNetworkCap: boolean
			hiddenColumns: string[]
			truncateRowValues: number | string
			snippetsPerPage: number
			typeCounts?: Record<string, number>
			cloudSearchPerPage: number
			isSafeModeActive: boolean
			bulkDownloadNonce: string
			supportsZipDownloads: boolean
			editorTheme: string
		}
		readonly CODE_SNIPPETS_EDIT?: {
			snippet: SnippetSchema
			pageTitleActions: Record<string, string>
			isLicensed: boolean
			enableDownloads: boolean
			activateByDefault: boolean
			enableDescription: boolean
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
		readonly CODE_SNIPPETS_WELCOME?: {
			hero: {
				name: string
				follow_url: string
				image_url: string
			}
			changelog: ChangelogSchema[]
			features: ImageLinkSchema[]
			partners: ImageLinkSchema[]
		}
		readonly CODE_SNIPPETS_INSIGHTS?: InsightsSummary
	}
}
