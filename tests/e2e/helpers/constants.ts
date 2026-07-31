export const SELECTORS = <const> {
	TITLE_INPUT: '#title',
	CODE_MIRROR_TEXTAREA: '.CodeMirror textarea',
	SNIPPET_TYPE_SELECT: '.snippet-type-container .code-snippets-select',
	LOCATION_SELECT: '.code-snippets-select-location',

	SUCCESS_MESSAGE: '.snippet-editor-sidebar .notice.updated',
	SAVE_SETTLED_NOTICE: '.snippet-editor-sidebar .notice.updated, .code-snippets-notice.error',

	SNIPPETS_TABLE: '.wp-list-table',
	SNIPPET_ROW: '.wp-list-table tbody tr',
	SNIPPET_TOGGLE: 'input.switch',
	SNIPPET_NAME_LINK: '.snippet-name',
	SNIPPET_SEARCH_INPUT: '#snippets_search',

	PREVIEW_ACTION: '.row-actions button:has-text("Preview")',
	CLONE_ACTION: '.row-actions button:has-text("Clone")',
	DELETE_ACTION: '.row-actions button:has-text("Trash")',
	EXPORT_ACTION: '.row-actions button:has-text("Export")',

	ADMIN_BAR: '#wpadminbar',
	THEME_MAIN_WRAPPER: '.wp-site-blocks'
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
