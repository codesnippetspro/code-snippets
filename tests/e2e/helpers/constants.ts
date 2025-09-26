export const SELECTORS = {
  WPBODY_CONTENT: '#wpbody-content, .wrap, #wpcontent',
  PAGE_TITLE: 'h1, .page-title',
  ADD_NEW_BUTTON: '.page-title-action',

  TITLE_INPUT: '#title',
  CODE_MIRROR_TEXTAREA: '.CodeMirror textarea',
  SNIPPET_TYPE_SELECT: '#snippet-type-select-input',
  LOCATION_SELECT: '.code-snippets-select-location',

  SUCCESS_MESSAGE: '#message.notice',
  SUCCESS_MESSAGE_P: '#message.notice p',

  DELETE_CONFIRM_BUTTON: 'button.components-button.is-destructive.is-primary',

  SNIPPETS_TABLE: '.wp-list-table',
  SNIPPET_ROW: '.wp-list-table tbody tr',
  SNIPPET_TOGGLE: '.snippet-activation-switch input[type="checkbox"]',
  SNIPPET_NAME_LINK: '.row-title',

  EDIT_ACTION: '.row-actions .edit a',
  CLONE_ACTION: '.row-actions .clone a',
  DELETE_ACTION: '.row-actions .delete a',
  EXPORT_ACTION: '.row-actions .export a',

  ADMIN_BAR: '#wpadminbar',
} as const;
  
export const TIMEOUTS = {
  DEFAULT: 10000,
  SHORT: 5000,
} as const;

export const URLS = {
  SNIPPETS_ADMIN: '/wp-admin/admin.php?page=snippets',
  FRONTEND: '/',
} as const;

export const MESSAGES = {
  SNIPPET_CREATED: 'Snippet created',
  SNIPPET_CREATED_AND_ACTIVATED: 'Snippet created and activated',
  SNIPPET_UPDATED_AND_ACTIVATED: 'Snippet updated and activated',
  SNIPPET_UPDATED_AND_DEACTIVATED: 'Snippet updated and deactivated',
} as const;

export const SNIPPET_TYPES = {
  PHP: 'PHP',
  HTML: 'HTML',
  CSS: 'CSS',
  JS: 'JS',
} as const;

export const SNIPPET_LOCATIONS = {
  SITE_FOOTER: 'In site footer',
  SITE_HEADER: 'In site <head> section',
  IN_EDITOR: 'Where inserted in editor',
  ADMIN_ONLY: 'Only run in administration area',
  CSS_ADMIN_ONLY: 'Administration area',
  FRONTEND_ONLY: 'Only run on site front-end',
  CSS_FRONTEND_ONLY: 'Site front-end',
  EVERYWHERE: 'Run everywhere',
} as const;

export const BUTTONS = {
  SAVE: 'text=Save Snippet',
  SAVE_AND_ACTIVATE: 'text=Save and Activate',
  SAVE_AND_DEACTIVATE: 'text=Save and Deactivate',
  DELETE: 'text=Delete',
} as const;
  
