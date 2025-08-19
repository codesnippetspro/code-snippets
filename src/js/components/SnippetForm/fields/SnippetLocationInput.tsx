import { __ } from '@wordpress/i18n'
import React from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { SNIPPET_TYPE_SCOPES } from '../../../types/Snippet'
import { getSnippetType, isCondition } from '../../../utils/snippets/snippets'
import type { SnippetCodeScope } from '../../../types/Snippet'
import type { SelectOption } from '../../../types/SelectOption'

const SCOPE_ICONS: Record<SnippetCodeScope, string> = {
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

const SCOPE_DESCRIPTIONS: Record<SnippetCodeScope, string> = {
	'global': __('Run everywhere', 'code-snippets'),
	'admin': __('Only run in administration area', 'code-snippets'),
	'front-end': __('Only run on site front-end', 'code-snippets'),
	'single-use': __('Only run once', 'code-snippets'),
	'content': __('Where inserted in editor', 'code-snippets'),
	'head-content': __('In site <head> section', 'code-snippets'),
	'footer-content': __('In site footer (end of <body>)', 'code-snippets'),
	'site-css': __('Site front-end', 'code-snippets'),
	'admin-css': __('Administration area', 'code-snippets'),
	'site-footer-js': __('In site footer (end of <body>)', 'code-snippets'),
	'site-head-js': __('In site <head> section', 'code-snippets')
}

export const SnippetLocationInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	const options: SelectOption<SnippetCodeScope>[] = SNIPPET_TYPE_SCOPES[getSnippetType(snippet)]
		.filter(scope => 'condition' !== scope)
		.map(scope => ({
			key: scope,
			value: scope,
			label: SCOPE_DESCRIPTIONS[scope]
		}))

	return isCondition(snippet)
		? null
		: <div className="block-form-field">
			<h4><label htmlFor="snippet-location">{__('Location', 'code-snippets')}</label></h4>
			<Select
				inputId="snippet-location"
				className="code-snippets-select"
				options={options}
				isDisabled={isReadOnly}
				styles={{
					menu: provided => ({ ...provided, zIndex: 9999 }),
					input: provided => ({ ...provided, ':focus': { boxShadow: 'none' } })
				}}
				value={options.find(option => option.value === snippet.scope)}
				formatOptionLabel={({ label, value }) =>
					<>
						<span className={`dashicons dashicons-${SCOPE_ICONS[value]}`}></span>{` ${label}`}
					</>
				}
				onChange={option =>
					option?.value && setSnippet(previous => ({ ...previous, scope: option.value }))}
			/>
		</div>
}
