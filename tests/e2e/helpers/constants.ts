export const SELECTORS = <const>{
	TITLE_INPUT: '#title',
	CODE_MIRROR_TEXTAREA: '.CodeMirror textarea',
	SNIPPET_TYPE_SELECT: '#snippet-type-select-input',
	LOCATION_SELECT: '.code-snippets-select-location',

	SUCCESS_MESSAGE: '.snippet-editor-sidebar .notice.updated',

	SNIPPETS_TABLE: '.wp-list-table',
	SNIPPET_ROW: '.wp-list-table tbody tr',
	SNIPPET_TOGGLE: 'input.switch',
	SNIPPET_NAME_LINK: '.snippet-name',

	CLONE_ACTION: '.row-actions button:has-text("Clone")',
	DELETE_ACTION: '.row-actions button:has-text("Clone")',
	EXPORT_ACTION: '.row-actions button:has-text("Export")',

	ADMIN_BAR: '#wpadminbar'
}

export const TIMEOUTS = <const>{
	DEFAULT: 10000,
	SHORT: 5000
}

export const URLS = <const>{
	SNIPPETS_ADMIN: '/wp-admin/admin.php?page=snippets',
	ADD_SNIPPET: '/wp-admin/admin.php?page=add-snippet',
	FRONTEND: '/'
}

export const MESSAGES = <const>{
	SNIPPET_CREATED: 'Snippet created',
	SNIPPET_CREATED_AND_ACTIVATED: 'Snippet created and activated',
	SNIPPET_UPDATED_AND_ACTIVATED: 'Snippet updated and activated',
	SNIPPET_UPDATED_AND_DEACTIVATED: 'Snippet updated and deactivated'
}

export const SNIPPET_TYPES = <const>{
	PHP: 'Functions',
	HTML: 'Content'
}

export const SNIPPET_LOCATIONS = <const>{
	SITE_FOOTER: 'In site footer (end of <body>)',
	SITE_HEADER: 'In site <head> section',
	IN_EDITOR: 'Where inserted in editor',
	ADMIN_ONLY: 'Only run in administration area',
	FRONTEND_ONLY: 'Only run on site front-end',
	EVERYWHERE: 'Run everywhere'
}

export const BUTTONS = <const>{
	SAVE: 'text=Save Snippet',
	SAVE_AND_ACTIVATE: 'text=Save and Activate',
	SAVE_AND_DEACTIVATE: 'text=Save and Deactivate',
	DELETE: 'button.delete-button:has-text("Trash")'
}
