import { __, sprintf } from '@wordpress/i18n'
import { isLicensed, isNetworkAdmin } from '../screen'
import { buildUrl } from '../urls'
import { parseSnippetObject } from './objects'
import type { SnippetSchema } from '../../types/schema/SnippetSchema'
import type { Snippet, SnippetCodeScope, SnippetScope, SnippetType } from '../../types/Snippet'

const PRO_TYPES = new Set<SnippetType>(['css', 'js', 'cond'])

export const SNIPPET_TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions', 'code-snippets'),
	html: __('Content', 'code-snippets'),
	css: __('Styles', 'code-snippets'),
	js: __('Scripts', 'code-snippets'),
	cond: __('Conditions', 'code-snippets')
}

export const SNIPPET_SCOPE_ICONS: Record<SnippetCodeScope, string> = {
	'global': 'admin-site',
	'admin': 'admin-tools',
	'front-end': 'admin-appearance',
	'single-use': 'clock',
	'content': 'shortcode',
	'head-content': 'editor-code',
	'body-content': 'editor-code',
	'footer-content': 'editor-code',
	'admin-css': 'dashboard',
	'site-css': 'admin-customizer',
	'site-head-js': 'media-code',
	'site-footer-js': 'media-code'
}

export const SNIPPET_SCOPE_DESCRIPTIONS: Record<SnippetCodeScope, string> = {
	'global': __('Run everywhere', 'code-snippets'),
	'admin': __('Only run in administration area', 'code-snippets'),
	'front-end': __('Only run on site front-end', 'code-snippets'),
	'single-use': __('Only run once', 'code-snippets'),
	'content': __('Where inserted in editor', 'code-snippets'),
	'head-content': __('In site header (<head> section)', 'code-snippets'),
	'body-content': __('In site content (start of <body>)', 'code-snippets'),
	'footer-content': __('In site footer (end of <body>)', 'code-snippets'),
	'site-css': __('Site front-end', 'code-snippets'),
	'admin-css': __('Administration area', 'code-snippets'),
	'site-head-js': __('In site header (<head> section)', 'code-snippets'),
	'site-footer-js': __('In site footer (end of <body>)', 'code-snippets')
}

export const createSnippetObject = (fields?: Partial<Snippet> | Partial<SnippetSchema>): Snippet =>
	parseSnippetObject(fields)

export const cloneSnippetObject = (snippet: Snippet): Snippet =>
	createSnippetObject({
		...snippet,
		id: 0,
		active: false,
		// translators: %s: snippet title.
		name: sprintf(__('%s [CLONE]', 'code-snippets'), snippet.name)
	})

export const getSnippetType = ({ scope }: Pick<Snippet, 'scope'>): SnippetType => {
	switch (true) {
		case scope.endsWith('-css'):
			return 'css'

		case scope.endsWith('-js'):
			return 'js'

		case scope.endsWith('content'):
			return 'html'

		case 'condition' === scope:
			return 'cond'

		default:
			return 'php'
	}
}

export const getSnippetEditUrl = (snippet?: Pick<Snippet, 'id'>): string | undefined =>
	snippet?.id
		? buildUrl(window.CODE_SNIPPETS?.urls.edit, { id: snippet.id })
		: window.CODE_SNIPPETS?.urls.addNew

export const getSnippetAddNewUrl = (type?: SnippetType): string | undefined =>
	type && (isLicensed() || !isProType(type))
		? buildUrl(window.CODE_SNIPPETS?.urls.addNew, { type })
		: window.CODE_SNIPPETS?.urls.addNew

export const getSnippetDisplayName = (snippet: Pick<Snippet, 'name' | 'id' | 'scope'>): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(isCondition(snippet) ? __('Condition #%d', 'code-snippets') : __('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

export const validateSnippet = (snippet: Snippet): undefined | string => {
	const missingTitle = '' === snippet.name.trim()
	const missingCode = '' === snippet.code.trim()

	switch (true) {
		case missingCode && missingTitle:
			return __('This snippet has no code or title.', 'code-snippets')

		case missingCode:
			return __('This snippet has no snippet code.', 'code-snippets')

		case missingTitle:
			return __('This snippet has no title.', 'code-snippets')

		default:
			return undefined
	}
}

export const isCondition = (snippet: { scope?: SnippetScope }): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)

export const isSnippetActive = (
	snippet: Snippet,
	activeByCondition: Map<Snippet['id'], Snippet[]>
): boolean =>
	'cond' === getSnippetType(snippet)
		? 0 < (activeByCondition.get(snippet.id)?.length ?? 0)
		: snippet.active

/**
 * Whether the snippet belongs to the network and is not shared with subsites,
 * making it read-only outside of the network admin.
 */
export const isNetworkOnlySnippet = (snippet: Snippet): boolean =>
	!isNetworkAdmin() && snippet.network && !snippet.shared_network

/**
 * Whether the current user is able to modify the snippet, either directly or
 * through actions such as cloning and trashing.
 */
export const canModifySnippet = (snippet: Snippet): boolean =>
	!isNetworkOnlySnippet(snippet) &&
	!(snippet.shared_network && !window.CODE_SNIPPETS_MANAGE?.hasNetworkCap)
