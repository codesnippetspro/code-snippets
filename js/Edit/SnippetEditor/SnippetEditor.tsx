import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { EditorConfiguration } from 'codemirror'
import React, { Dispatch, SetStateAction, useEffect } from 'react'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'
import { ConditionEditor } from '../ConditionEditor'
import { CodeEditorInstance } from '../../types/editor'
import { SNIPPET_TYPE_SCOPES, SNIPPET_TYPES, SnippetType } from '../../types/Snippet'
import '../../editor'
import { getSnippetType, isProType } from '../../utils/snippets'
import classnames from 'classnames'
import { CodeEditor } from './CodeEditor'

interface SnippetTypeTabProps extends Pick<BaseSnippetProps, 'setSnippet'> {
	tabType: SnippetType
	label: string
	currentType: SnippetType
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ tabType, label, currentType, setSnippet }) =>
	<a
		data-snippet-type={tabType}
		className={classnames({
			'nav-tab': true,
			'nav-tab-active': tabType === currentType,
			'nav-tab-inactive': isProType(tabType)
		})}
		{...isProType(tabType) ?
			{
				title: __('Available in Code Snippets Pro (external link)', 'code-snippets'),
				href: 'https://codesnippets.pro/pricing/',
				target: '_blank'
			} :
			{
				href: addQueryArgs(window.location.href, { type: tabType }),
				onClick: event => {
					event.preventDefault()
					const scope = SNIPPET_TYPE_SCOPES[tabType][0]
					setSnippet(previous => ({ ...previous, scope }))
				}
			}
		}>
		{`${label} `}

		{'cond' === tabType ?
			<span className="dashicons dashicons-randomize"></span> :
			<span className="badge">{tabType}</span>}
	</a>

export interface SnippetEditorProps extends BaseSnippetProps {
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions', 'code-snippets'),
	html: __('Content', 'code-snippets'),
	css: __('Styles', 'code-snippets'),
	js: __('Scripts', 'code-snippets'),
	cond: __('Conditions', 'code-snippets')
}

const EDITOR_MODES: Partial<Record<SnippetType, string>> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

export const SnippetEditor: React.FC<SnippetEditorProps> = ({
	snippet,
	setSnippet,
	codeEditorInstance,
	setCodeEditorInstance
}) => {
	const snippetType = getSnippetType(snippet)

	useEffect(() => {
		codeEditorInstance?.codemirror.setOption('lint' as keyof EditorConfiguration, 'php' === snippetType || 'css' === snippetType)

		if (snippetType in EDITOR_MODES) {
			codeEditorInstance?.codemirror.setOption('mode', EDITOR_MODES[snippetType])
		}
	}, [codeEditorInstance, snippetType])

	return (
		<>
			<h2>
				<label htmlFor="snippet_code">
					{`${__('Code', 'code-snippets')} `}
					{snippet.id ?
						<span
							className="snippet-type-badge"
							data-snippet-type={snippetType}
						>{snippetType}</span> :
						''}
				</label>
			</h2>

			{snippet.id || window.CODE_SNIPPETS_EDIT?.isPreview ? '' :
				<h2 className="nav-tab-wrapper" id="snippet-type-tabs">
					{SNIPPET_TYPES.map(type =>
						<SnippetTypeTab
							key={type}
							tabType={type}
							label={TYPE_LABELS[type]}
							currentType={getSnippetType(snippet)}
							setSnippet={setSnippet}
						/>)}
				</h2>}

			<ConditionEditor snippet={snippet} setSnippet={setSnippet} />
			<CodeEditor snippet={snippet} setSnippet={setSnippet} setEditorInstance={setCodeEditorInstance} />
		</>
	)
}
