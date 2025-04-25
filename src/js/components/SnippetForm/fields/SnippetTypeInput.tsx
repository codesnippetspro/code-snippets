import React, { useEffect } from 'react'
import { __, _x } from '@wordpress/i18n'
import Select from 'react-select'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { SNIPPET_TYPE_SCOPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { getSnippetType, isCondition, isProType } from '../../../utils/snippets/snippets'
import { SnippetTypeBadge } from '../../common/SnippetTypeBadge'
import type { SnippetCodeType, SnippetType } from '../../../types/Snippet'
import type { SelectOption } from '../../../types/SelectOption'
import type { EditorConfiguration } from 'codemirror'

export interface SnippetTypeInputProps {
	openUpgradeDialog: VoidFunction
}

const EDITOR_MODES: Record<SnippetCodeType, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

const OPTIONS: SelectOption<SnippetType>[] = [
	{ value: 'php', label: __('Functions', 'code-snippets') },
	{ value: 'html', label: __('Content', 'code-snippets') },
	{ value: 'css', label: __('Styles', 'code-snippets') },
	{ value: 'js', label: __('Scripts', 'code-snippets') },
	{ value: 'cond', label: __('Conditions', 'code-snippets') }
]

const SnippetTypeOption: React.FC<SelectOption<SnippetType>> = ({ label, value }) =>
	<div className="snippet-type-option">
		<div>
			{label}
			{isProType(value) && !isLicensed() &&
				<span className="badge go-pro-badge">{_x('Pro', 'Upgrade to Pro', 'code-snippets')}</span>}
		</div>
		<SnippetTypeBadge snippetType={value} />
	</div>

export const SnippetTypeInput: React.FC<SnippetTypeInputProps> = ({ openUpgradeDialog }) => {
	const { snippet, setSnippet, codeEditorInstance } = useSnippetForm()
	const snippetType = getSnippetType(snippet)

	useEffect(() => {
		if (codeEditorInstance) {
			const codeEditor = codeEditorInstance.codemirror

			codeEditor.setOption('lint' as keyof EditorConfiguration, 'php' === snippetType || 'css' === snippetType)

			if ('cond' !== snippetType && EDITOR_MODES[snippetType]) {
				codeEditor.setOption('mode', EDITOR_MODES[snippetType])
				codeEditor.refresh()
			}
		}
	}, [codeEditorInstance, snippetType])

	return (
		<div className="snippet-type-container">
			<label><h3>{__('Snippet Type', 'code-snippets')}</h3></label>
			<Select
				className="code-snippets-select"
				options={OPTIONS}
				isDisabled={0 !== snippet.id && isCondition(snippet)}
				value={OPTIONS.find(option => option.value === snippetType)}
				styles={{
					menu: provided => ({ ...provided, zIndex: 9999 }),
					input: provided => ({ ...provided, boxShadow: 'none' })
				}}
				formatOptionLabel={SnippetTypeOption}
				onChange={option => {
					if (option && isProType(option.value) && !isLicensed()) {
						openUpgradeDialog()
					} else if (option) {
						setSnippet(previous => ({
							...previous,
							scope: SNIPPET_TYPE_SCOPES[option.value][0]
						}))
					}
				}}
			/>
		</div>
	)
}
