import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'
import { Snippet, SNIPPET_TYPE_SCOPES, SNIPPET_TYPES, SnippetScope } from '../../types/Snippet'
import { isNetworkAdmin } from '../../utils/general'
import { getSnippetType } from '../../utils/snippets'
import { CopyToClipboardButton } from '../../common/CopyToClipboardButton'

const SHORTCODE_TAG = 'code_snippet'

const SCOPE_ICONS: Record<SnippetScope, string> = {
	'global': 'admin-site',
	'admin': 'admin-tools',
	'front-end': 'admin-appearance',
	'single-use': 'clock',
	'content': 'shortcode',
	'head-content': 'editor-code',
	'footer-content': 'editor-code',
	'admin-css': 'dashboard',
	'site-css': 'admin-customizer',
	'site-head-js': 'media-code',
	'site-footer-js': 'media-code',
	'condition': 'randomize'
}

const SCOPE_DESCRIPTIONS: Record<SnippetScope, string> = {
	'global': __('Run snippet everywhere', 'code-snippets'),
	'admin': __('Only run in administration area', 'code-snippets'),
	'front-end': __('Only run on site front-end', 'code-snippets'),
	'single-use': __('Only run once', 'code-snippets'),
	'content': __('Only display when inserted into a post or page.', 'code-snippets'),
	'head-content': __('Display in site <head> section.', 'code-snippets'),
	'footer-content': __('Display at the end of the <body> section, in the footer.', 'code-snippets'),
	'site-css': __('Site front-end styles', 'code-snippets'),
	'admin-css': __('Administration area styles', 'code-snippets'),
	'site-footer-js': __('Load JS at the end of the &lt;body&gt; section', 'code-snippets'),
	'site-head-js': __('Load JS in the &lt;head&gt; section', 'code-snippets'),
	'condition': ''
}

interface ShortcodeOptions {
	php: boolean
	format: boolean
	shortcodes: boolean
}

const ShortcodeInfo: React.FC<{ snippet: Snippet }> = ({ snippet }) => {
	const [options, setOptions] = useState<ShortcodeOptions>(() => ({
		php: snippet.code.includes('<?'),
		format: true,
		shortcodes: false
	}))

	const shortcodeTag = [
		SHORTCODE_TAG,
		snippet.id ? `id=${snippet.id}` : '',
		snippet.network || isNetworkAdmin() ? 'network=true' : '',
		...Object.entries(options).map(([option, value]) => value ? `${option}=true` : '')
	].filter(Boolean).join(' ')

	const optionLabels: [keyof ShortcodeOptions, string][] = [
		['php', __('Evaluate PHP code', 'code-snippets')],
		['format', __('Add paragraphs and formatting', 'code-snippets')],
		['shortcodes', __('Evaluate additional shortcode tags', 'code-snippets')]
	]

	return snippet.id && 'content' === snippet.scope ?
		<>
			{/* eslint-disable-next-line max-len */}
			<p>{__('There are multiple options for inserting this snippet into a post, page or other content. You can copy the below shortcode, or use the Classic Editor button, Block Editor block (Pro) or Elementor widget (Pro).', 'code-snippets')}</p>

			<p>
				<code className="shortcode-tag">
					[{shortcodeTag}]
				</code>

				<CopyToClipboardButton
					title={__('Copy shortcode to clipboard', 'code-snippets')}
					text={`[${shortcodeTag}]`}
				/>
			</p>

			{snippet.id ?
				<p className="html-shortcode-options">
					<strong>{__('Shortcode Options: ', 'code-snippets')}</strong>
					{optionLabels.map(([option, label]) =>
						<label key={option}>
							<input type="checkbox" value={option} checked={options[option]} onChange={event => setOptions(value => {
								value[option] = event.target.checked
								return value
							})} />
							{` ${label}`}
						</label>
					)}
				</p> : ''}
		</> :
		null
}

export const ScopeInput: React.FC<BaseSnippetProps> = ({ snippet, setSnippetField }) =>
	<>
		<h2 className="screen-reader-text">{__('Scope', 'code-snippets')}</h2>

		{SNIPPET_TYPES
			.filter(type => 'cond' !== type && (!snippet.id || type === getSnippetType(snippet)))
			.map(type =>
				<p key={type} className={`snippet-scope ${type}-scopes-list`}>
					{SNIPPET_TYPE_SCOPES[type].map(scope =>
						<label key={scope}>
							<input
								type="radio"
								name="snippet_scope"
								value={scope}
								checked={scope === snippet.scope}
								onChange={event => event.target.checked && setSnippetField('scope', scope)}
							/>
							<span className={`dashicons dashicons-${SCOPE_ICONS[scope]}`}></span>
							{` ${SCOPE_DESCRIPTIONS[scope]}`}
						</label>)}

					{'html' === type ? <ShortcodeInfo snippet={snippet} /> : null}
				</p>
			)}
	</>
