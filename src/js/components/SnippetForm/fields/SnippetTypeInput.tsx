import React, { useEffect } from 'react'
import classnames from 'classnames'
import { __, _x } from '@wordpress/i18n'
import Select from 'react-select'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { SNIPPET_TYPE_SCOPES } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/screen'
import { getSnippetType, isProType } from '../../../utils/snippets/snippets'
import { Badge } from '../../common/Badge'
import type { Dispatch, SetStateAction } from 'react'
import type { SnippetCodeType, SnippetType } from '../../../types/Snippet'
import type { SelectOption } from '../../../types/SelectOption'
import type { EditorConfiguration } from 'codemirror'

export interface SnippetTypeInputProps {
	setIsUpgradeDialogOpen: Dispatch<SetStateAction<boolean>>
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
	<div className={classnames('snippet-type-option', { 'inverted-badges': isProType(value) && !isLicensed() })}>
		<div>
			{label}
			{isProType(value) && !isLicensed()
				? <span className="badge pro-badge small-badge">{_x('Pro', 'Upgrade to Pro', 'code-snippets')}</span>
				: null}
		</div>
		<Badge name={value} />
	</div>

export const SnippetTypeInput: React.FC<SnippetTypeInputProps> = ({ setIsUpgradeDialogOpen }) => {
	const { snippet, setSnippet, codeEditorInstance, isReadOnly } = useSnippetForm()
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
			<label htmlFor="snippet-type-select-input">
				<h3>{__('Snippet Type', 'code-snippets')}</h3>
			</label>
			<Select
				inputId="snippet-type-select-input"
				className="code-snippets-select"
				isDisabled={isReadOnly}
				options={0 === snippet.id ? OPTIONS : OPTIONS.filter(option => 'cond' !== option.value)}
				styles={{
					menu: provided => ({ ...provided, zIndex: 9999 }),
					input: provided => ({ ...provided, boxShadow: 'none' })
				}}
				value={OPTIONS.find(option => option.value === snippetType)}
				formatOptionLabel={SnippetTypeOption}
				onChange={option => {
					if (option && isProType(option.value) && !isLicensed()) {
						setIsUpgradeDialogOpen(true)
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
