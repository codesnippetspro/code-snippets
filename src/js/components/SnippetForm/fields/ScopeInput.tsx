import { ExternalLink } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { SNIPPET_TYPE_SCOPES } from '../../../types/Snippet'
import { isNetworkAdmin } from '../../../utils/conditions'
import { buildShortcodeTag } from '../../../utils/shortcodes'
import { getSnippetType } from '../../../utils/snippets'
import { CopyToClipboardButton } from '../../common/CopyToClipboardButton'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { ConditionalSelector } from './ConditionalSelector'
import type { ShortcodeAtts } from '../../../utils/shortcodes'
import type { SnippetScope } from '../../../types/Snippet'
import type { Dispatch, SetStateAction } from 'react'

const SHORTCODE_TAG = 'code_snippet'

const SCOPE_ICONS: Partial<Record<SnippetScope, string>> = {
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
	'site-footer-js': 'media-code'
}

const SCOPE_DESCRIPTIONS: Partial<Record<SnippetScope, string>> = {
	'global': __('Run snippet everywhere', 'code-snippets'),
	'admin': __('Only run in administration area', 'code-snippets'),
	'front-end': __('Only run on site front-end', 'code-snippets'),
	'single-use': __('Only run once', 'code-snippets'),
	'content': __('Only display when inserted into a post or page.', 'code-snippets'),
	'head-content': __('Display in site <head> section.', 'code-snippets'),
	'footer-content': __('Display at the end of the <body> section, in the footer.', 'code-snippets'),
	'site-css': __('Site front-end styles', 'code-snippets'),
	'admin-css': __('Administration area styles', 'code-snippets'),
	'site-footer-js': __('Load JS at the end of the <body> section', 'code-snippets'),
	'site-head-js': __('Load JS in the <head> section', 'code-snippets')
}

interface ShortcodeOptions {
	php: boolean
	format: boolean
	shortcodes: boolean
}

const ShortcodeTag: React.FC<{ atts: ShortcodeAtts }> = ({ atts }) =>
	<p>
		<code className="shortcode-tag">{buildShortcodeTag(SHORTCODE_TAG, atts)}</code>

		<CopyToClipboardButton
			title={__('Copy shortcode to clipboard', 'code-snippets')}
			text={buildShortcodeTag(SHORTCODE_TAG, atts)}
		/>
	</p>

interface ShortcodeOptionsProps {
	optionLabels: [keyof ShortcodeOptions, string][]
	options: ShortcodeOptions
	setOptions: Dispatch<SetStateAction<ShortcodeOptions>>
	isReadOnly: boolean
}

const ShortcodeOptions: React.FC<ShortcodeOptionsProps> = ({
	optionLabels,
	options,
	setOptions,
	isReadOnly
}) =>
	<p className="html-shortcode-options">
		<strong>{__('Shortcode Options: ', 'code-snippets')}</strong>
		{optionLabels.map(([option, label]) =>
			<label key={option}>
				<input
					type="checkbox"
					value={option}
					checked={options[option]}
					disabled={isReadOnly}
					onChange={event =>
						setOptions(previous => ({ ...previous, [option]: event.target.checked }))}
				/>
				{` ${label}`}
			</label>
		)}
	</p>

const ShortcodeInfo: React.FC = () => {
	const { snippet, isReadOnly } = useSnippetForm()
	const [options, setOptions] = useState<ShortcodeOptions>(() => ({
		php: snippet.code.includes('<?'),
		format: true,
		shortcodes: false
	}))

	return 'content' === snippet.scope ?
		<>
			<p className="description">
				{__('There are multiple options for inserting this snippet into a post, page or other content.', 'code-snippets')}
				{' '}
				{snippet.id ?
					// eslint-disable-next-line @stylistic/max-len
					__('You can copy the below shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-snippets') :
					// eslint-disable-next-line @stylistic/max-len
					__('After saving, you can copy a shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-snippets')}
				{' '}
				<ExternalLink
					href={__('https://help.codesnippets.pro/article/50-inserting-snippets', 'code-snippets')}
				>
					{__('Learn more', 'code-snippets')}
				</ExternalLink>
			</p>

			{snippet.id ?
				<>
					<ShortcodeTag atts={{
						id: snippet.id,
						network: snippet.network ?? isNetworkAdmin(),
						...options
					}} />

					<ShortcodeOptions
						options={options}
						setOptions={setOptions}
						isReadOnly={isReadOnly}
						optionLabels={[
							['php', __('Evaluate PHP code', 'code-snippets')],
							['format', __('Add paragraphs and formatting', 'code-snippets')],
							['shortcodes', __('Evaluate additional shortcode tags', 'code-snippets')]
						]}
					/>
				</> : null}
		</> : null
}

export const ScopeInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	const snippetType = getSnippetType(snippet)

	return <>
		<h2 className="screen-reader-text">{__('Scope', 'code-snippets')}</h2>

		<p className={`snippet-scope ${snippetType}-scopes-list`}>
			{SNIPPET_TYPE_SCOPES[snippetType].map(scope =>
				<div key={scope} className={scope.startsWith('conditional-') ? 'conditional-scope-select' : ''}>
					<label>
						<input
							type="radio"
							name="snippet_scope"
							value={scope}
							checked={scope === snippet.scope}
							onChange={event =>
								event.target.checked && setSnippet(previous => ({
									...previous,
									scope
								}))
							}
							disabled={isReadOnly}
						/>
						{' '}

						{scope.startsWith('conditional-') ?
							<>
								<span className="dashicons dashicons-randomize"></span>
								{` ${__('Conditional', 'code-snippets')}`}
							</> :
							<>
								<span className={`dashicons dashicons-${SCOPE_ICONS[scope]}`}></span>
								{` ${SCOPE_DESCRIPTIONS[scope]}`}
							</>
						}
					</label>

					{scope.startsWith('conditional-') && <ConditionalSelector />}
				</div>
			)}

			{'html' === snippetType ? <ShortcodeInfo /> : null}
		</p>
	</>
}
