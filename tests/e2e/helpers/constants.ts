export const SELECTORS = <const> {
	// Input fields
	TITLE_INPUT: '#title',
	CODE_MIRROR: '.CodeMirror',
	CODE_MIRROR_TEXTAREA: '.CodeMirror textarea',
	SNIPPET_TYPE_SELECT: '.snippet-type-container .code-snippets-select',
	LOCATION_SELECT: '.code-snippets-select-location',

	// Messages
	SUCCESS_MESSAGE: '.snippet-editor-sidebar .notice.updated',
	SAVE_SETTLED_NOTICE: '.snippet-editor-sidebar .notice.updated, .code-snippets-notice.error',

	// Snippets table
	SNIPPETS_TABLE: '.wp-list-table',
	SNIPPET_ROW: 'table.wp-list-table tbody tr',
	SNIPPET_TOGGLE: 'input.switch',
	SNIPPET_NAME_LINK: 'a.snippet-name',
	SNIPPET_SEARCH_INPUT: '#snippets_search',

	// Row actions (use role-based selectors for better performance)
	PREVIEW_ACTION: 'button[data-action="preview"]',
	CLONE_ACTION: 'button[data-action="clone"]',
	DELETE_ACTION: 'button[data-action="trash"]',
	EXPORT_ACTION: 'button[data-action="export"]',

	// UI elements
	ADMIN_BAR: '#wpadminbar',
	THEME_MAIN_WRAPPER: '.wp-site-blocks',

	// Dialogs
	CONFIRM_DIALOG: '[role="dialog"][role="alertdialog"]',
	MODAL: '[role="dialog"]'
}

export const TIMEOUTS = <const> {
	DEFAULT: 30000,
	SHORT: 5000
}

export const URLS = <const> {
	SNIPPETS_ADMIN: '/wp-admin/admin.php?page=snippets',
	COMMUNITY_CLOUD_ADMIN: '/wp-admin/admin.php?page=snippets&subpage=cloud-community',
	ADD_SNIPPET_ADMIN: '/wp-admin/admin.php?page=add-snippet',
	IMPORT_SNIPPETS_ADMIN: '/wp-admin/admin.php?page=import-code-snippets',
	SETTINGS_ADMIN: '/wp-admin/admin.php?page=snippets-settings',
	WELCOME_SCREEN_ADMIN: '/wp-admin/admin.php?page=code-snippets-welcome',
	ADD_SNIPPET: '/wp-admin/admin.php?page=add-snippet',
	COMMUNITY_CLOUD: '/wp-admin/admin.php?page=snippets&subpage=cloud-community',
	FRONTEND: '/'
}

export const MESSAGES = <const> {
	SNIPPET_CREATED: /Snippet (?:created|updated)/i,
	SNIPPET_CREATED_AND_ACTIVATED: /Snippet (?:created|updated)(?: and activated)?/i,
	SNIPPET_UPDATED_AND_ACTIVATED: /Snippet updated/i,
	SNIPPET_UPDATED_AND_DEACTIVATED: /Snippet updated/i
}

export const SNIPPET_TYPES = <const> {
	PHP: 'Functions',
	HTML: 'Content'
}

export const SNIPPET_LOCATIONS = <const> {
	SITE_HEADER: 'In site header (<head> section)',
	SITE_BODY: 'In site content (start of <body>)',
	SITE_FOOTER: 'In site footer (end of <body>)',
	IN_EDITOR: 'Where inserted in editor',
	ADMIN_ONLY: 'Only run in administration area',
	FRONTEND_ONLY: 'Only run on site front-end',
	EVERYWHERE: 'Run everywhere'
}

export const BUTTONS = <const> {
	SAVE: 'role=button[name="Save Snippet"]',
	SAVE_AND_ACTIVATE: 'role=button[name="Save and Activate"]',
	DELETE: 'button:has-text("Trash")'
}

/**
 * Selector builder functions for optimized element selection.
 * These functions help avoid complex :has() and :has-text() selectors which are slower.
 */
export const selectorBuilders = {
	/**
	 * Build a selector for a snippet row by name using role-based queries when possible,
	 * falling back to filtered selectors for better performance.
	 * Much faster than: `.wp-list-table tbody tr:has(a.snippet-name:has-text("${name}"))`
	 */
	snippetRowByName: (name: string): string => {
		// Escape special characters in the name for use in attribute selectors
		const escaped = name.replace(/"/g, '\\"').replace(/\\/g, '\\\\')
		return `${SELECTORS.SNIPPET_ROW}:has(> td:nth-child(2) ${SELECTORS.SNIPPET_NAME_LINK}:contains("${escaped}"))`
	},

	/**
	 * Build a selector for a snippet toggle switch by finding the parent row first,
	 * then selecting the switch within it. Avoids repeated :has-text() evaluation.
	 */
	snippetToggleInRow: (rowSelector: string): string => {
		return `${rowSelector} ${SELECTORS.SNIPPET_TOGGLE}`
	},

	/**
	 * Build a selector for text node matching in a more efficient way.
	 * Avoid using :has-text() as it requires text matching for every element traversal.
	 * Instead, filter elements after querying.
	 */
	elementWithExactText: (selector: string, text: string): string => {
		// Use data-text attribute if available, otherwise use text content
		return `${selector}[data-text="${text}"], ${selector}:exact("${text}")`
	}
}
