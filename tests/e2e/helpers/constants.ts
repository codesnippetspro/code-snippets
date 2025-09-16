export const SELECTORS = {
  // Page elements
  WPBODY_CONTENT: '#wpbody-content, .wrap, #wpcontent',
  PAGE_TITLE: 'h1, .page-title',
  ADD_NEW_BUTTON: '.page-title-action',
  
  // Form elements
  TITLE_INPUT: '#title',
  CODE_MIRROR_TEXTAREA: '.CodeMirror textarea',
  SNIPPET_TYPE_SELECT: '#snippet-type-select-input',
  LOCATION_SELECT: '.code-snippets-select-location',
  
  // Messages
  SUCCESS_MESSAGE: '#message.notice',
  SUCCESS_MESSAGE_P: '#message.notice p',
  
  // Buttons
  DELETE_CONFIRM_BUTTON: 'button.components-button.is-destructive.is-primary',
  
  // Admin bar
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
} as const;

export const SNIPPET_LOCATIONS = {
  SITE_FOOTER: 'In site footer',
  SITE_HEADER: 'In site <head> section',
  IN_EDITOR: 'Where inserted in editor',
  ADMIN_ONLY: 'Only run in administration area',
  FRONTEND_ONLY: 'Only run on site front-end',
} as const;

export const BUTTONS = {
  SAVE: 'text=Save Snippet',
  SAVE_AND_ACTIVATE: 'text=Save and Activate',
  SAVE_AND_DEACTIVATE: 'text=Save and Deactivate',
  DELETE: 'text=Delete',
} as const;
