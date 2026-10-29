import React, { useEffect, useId } from 'react'
import classnames from 'classnames'
import { __, _x } from '@wordpress/i18n'
import Select from 'react-select'
import { useSnippetForm } from '../WithSnippetFormContext'
import { SNIPPET_TYPES, SNIPPET_TYPE_SCOPES } from '../../../../types/Snippet'
import { isLicensed } from '../../../../utils/screen'
import { SNIPPET_TYPE_LABELS, getSnippetType, isProType } from '../../../../utils/snippets/snippets'
import { Badge } from '../../../common/Badge'
import type { FormatOptionLabelContext, StylesConfig } from 'react-select'
import type { Dispatch, SetStateAction } from 'react'
import type { SnippetCodeType, SnippetType } from '../../../../types/Snippet'
import type { SelectOption } from '../../../../types/SelectOption'
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

const OPTIONS: SelectOption<SnippetType>[] =
	SNIPPET_TYPES.map(type =>
		({ value: type, label: SNIPPET_TYPE_LABELS[type] }))

const SELECT_STYLES: StylesConfig<SelectOption<SnippetType>> = {
	menu: provided => ({
		...provided,
		zIndex: 9999,
		width: 'max-content',
		minWidth: '100%'
	}),
	input: provided => ({ ...provided, boxShadow: 'none' })
}

interface SnippetTypeOptionProps {
	option: SelectOption<SnippetType>
	context: FormatOptionLabelContext
}

const SnippetTypeOption: React.FC<SnippetTypeOptionProps> = ({
	option: { value, label },
	context
}) =>
	<div className={classnames('snippet-type-option', {
		'inverted-badges': isProType(value) && !isLicensed()
	})}>
		<span className="snippet-type-option-main">
			<Badge name={value} />
			{'menu' === context ? label : null}
		</span>
		{'menu' === context && isProType(value) && !isLicensed()
			? <Badge name="pro" small>{_x('Pro', 'Upgrade to Pro', 'code-snippets')}</Badge>
			: null}
	</div>

export const SnippetTypeInput: React.FC<SnippetTypeInputProps> = ({ setIsUpgradeDialogOpen }) => {
	const { snippet, setSnippet, codeEditorInstance, isReadOnly } = useSnippetForm()
	const snippetType = getSnippetType(snippet)
	const snippetTypeId = useId()

	useEffect(() => {
		if (codeEditorInstance) {
			const codeEditor = codeEditorInstance.codemirror

			codeEditor.setOption(
				'lint' as keyof EditorConfiguration,
				'php' === snippetType || 'css' === snippetType
			)

			if ('cond' !== snippetType && EDITOR_MODES[snippetType]) {
				codeEditor.setOption('mode', EDITOR_MODES[snippetType])
				codeEditor.refresh()
			}
		}
	}, [codeEditorInstance, snippetType])

	return (
		<div className="snippet-type-container">
			<label htmlFor={snippetTypeId} className="screen-reader-text">
				{__('Snippet Type', 'code-snippets')}
			</label>
			<Select<SelectOption<SnippetType>>
				inputId={snippetTypeId}
				className="code-snippets-select"
				isSearchable={false}
				isDisabled={isReadOnly || 0 !== snippet.id && 'cond' === snippetType}
				options={0 === snippet.id ? OPTIONS : OPTIONS.filter(option => 'cond' !== option.value)}
				menuPlacement="bottom"
				styles={SELECT_STYLES}
				value={OPTIONS.find(option => option.value === snippetType)}
				formatOptionLabel={(data, meta) =>
					<SnippetTypeOption option={data} context={meta.context} />}
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
