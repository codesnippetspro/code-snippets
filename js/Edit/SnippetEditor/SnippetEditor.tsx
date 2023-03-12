import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { EditorConfiguration } from 'codemirror'
import React, { Dispatch, SetStateAction, useEffect, useRef } from 'react'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'
import { ConditionEditor } from '../ConditionEditor'
import { CodeEditorInstance } from '../../types/editor'
import { SNIPPET_TYPE_SCOPES, SNIPPET_TYPES, SnippetType } from '../../types/Snippet'
import '../../editor'
import { getSnippetType, isProType } from '../../utils/snippets'
import classnames from 'classnames'
import { CodeEditor } from './CodeEditor'

interface SnippetTypeTabProps extends Pick<BaseSnippetProps, 'setSnippetField'> {
	tabType: SnippetType
	label: string
	currentType: SnippetType
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({ tabType, label, currentType, setSnippetField }) =>
	<a
		data-snippet-type={tabType}
		className={classnames({
			'nav-tab': true,
			'nav-tab-active': tabType === currentType,
			'nav-tab-inactive': isProType(tabType)
		})}
		onClick={event => {
			event.preventDefault()
			setSnippetField('scope', SNIPPET_TYPE_SCOPES[tabType][0])
		}}
		{...isProType(tabType) ?
			{
				title: __('Available in Code Snippets Pro (external link)', 'code-snippets'),
				href: 'https://codesnippets.pro/pricing/',
				target: '_blank'
			} :
			{ href: addQueryArgs(window.location.href, { type: tabType }) }
		}>
		{`${label} `}

		{'cond' === tabType ?
			<span className="dashicons dashicons-randomize"></span> :
			<span className="badge">{tabType}</span>}
	</a>

export interface CodeEditorProps extends BaseSnippetProps {
	editorInstance: CodeEditorInstance | undefined
	setEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
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

export const SnippetEditor: React.FC<CodeEditorProps> = ({ snippet, setSnippetField, editorInstance, setEditorInstance }) => {

	useEffect(() => {
		const type = getSnippetType(snippet)
		editorInstance?.codemirror.setOption('lint' as keyof EditorConfiguration, 'php' === type || 'css' === type)

		if (type in EDITOR_MODES) {
			editorInstance?.codemirror.setOption('mode', EDITOR_MODES[type])
		}
	}, [snippet, editorInstance])

	return (
		<>
			<h2>
				<label htmlFor="snippet_code">
					{`${__('Code', 'code-snippets')} `}
					{snippet.id ?
						<span
							className="snippet-type-badge"
							data-snippet-type={getSnippetType(snippet)}
						>
							{getSnippetType(snippet)}
						</span> :
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
							setSnippetField={setSnippetField}
						/>)}
				</h2>}

			<ConditionEditor snippet={snippet} setSnippetField={setSnippetField} />
			<CodeEditor snippet={snippet} setSnippetField={setSnippetField} setEditorInstance={setEditorInstance} />
		</>
	)
}
