import React from 'react'
import { __ } from '@wordpress/i18n'
import { Snippet, SnippetScope } from '../../types/Snippet'

const _SCOPE_LABELS: Partial<Record<SnippetScope, string>> = {
	'site-css': __('Site front-end stylesheet', 'code-snippets'),
	'admin-css': __('Administration area stylesheet', 'code-snippets'),
	'site-head-js': __('JavaScript loaded in the site &amp;lt;head&amp;gt; section', 'code-snippets'),
	'site-footer-js': __('JavaScript loaded just before the closing &amp;lt;/body&amp;gt; tag', 'code-snippets')
}

export interface PreviewButtonsProps {
	snippet: Snippet
}

export const PreviewButtons: React.FC<PreviewButtonsProps> = () =>
	<>

	</>
