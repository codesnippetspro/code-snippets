import { __ } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { Editor, EditorConfiguration } from 'codemirror'
import React, { Dispatch, SetStateAction, useEffect } from 'react'
import { SnippetActionsInputProps, SnippetInputProps } from '../../types/SnippetInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { Snippet, SNIPPET_TYPE_SCOPES, SNIPPET_TYPES, SnippetType } from '../../types/Snippet'
import '../../editor'
import { getSnippetType, isLicensed } from '../../utils/snippets'
import classnames from 'classnames'
import { CodeEditor } from './CodeEditor'

interface SnippetTypeTabProps extends Pick<SnippetInputProps, 'setSnippet'> {
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
			'nav-tab-inactive': !isLicensed(),
		})}
		{...!isLicensed() ?
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

		<span className="badge">{tabType}</span>
	</a>

export const TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions', 'code-snippets'),
	html: __('Content', 'code-snippets'),
	css: __('Styles', 'code-snippets'),
	js: __('Scripts', 'code-snippets')
}

const EDITOR_MODES: Partial<Record<SnippetType, string>> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

interface SnippetTypeTabsProps {
	codeEditor: Editor
	setSnippet: Dispatch<SetStateAction<Snippet>>
	snippetType: SnippetType
}

const SnippetTypeTabs: React.FC<SnippetTypeTabsProps> = ({ codeEditor, setSnippet, snippetType }) => {

	useEffect(() => {
		codeEditor.setOption('lint' as keyof EditorConfiguration, 'php' === snippetType || 'css' === snippetType)

		if (snippetType in EDITOR_MODES) {
			codeEditor.setOption('mode', EDITOR_MODES[snippetType])
			codeEditor.refresh()
		}
	}, [codeEditor, snippetType])


	return (
		<h2 className="nav-tab-wrapper" id="snippet-type-tabs">
			{SNIPPET_TYPES.map(type =>
				<SnippetTypeTab
					key={type}
					tabType={type}
					label={TYPE_LABELS[type]}
					currentType={snippetType}
					setSnippet={setSnippet}
				/>)}
		</h2>
	)
}

export interface SnippetEditorProps extends SnippetActionsInputProps {
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const SnippetEditor: React.FC<SnippetEditorProps> = ({
	snippet,
	setSnippet,
	codeEditorInstance,
	setCodeEditorInstance,
	...actionsProps
}) => {
	const snippetType = getSnippetType(snippet)

	return (
		<>
			<div className="snippet-code-container">
				<h2>
					<label htmlFor="snippet_code">
						{__('Code', 'code-snippets')}{' '}
						{snippet.id ?
							<span className="snippet-type-badge" data-snippet-type={snippetType}>{snippetType}</span> : null}
					</label>
				</h2>

				{snippet.id || window.CODE_SNIPPETS_EDIT?.isPreview || !codeEditorInstance ? '' :
					<SnippetTypeTabs
						setSnippet={setSnippet}
						snippetType={snippetType}
						codeEditor={codeEditorInstance.codemirror}
					/>}

				<CodeEditor
					snippet={snippet}
					setSnippet={setSnippet}
					editorInstance={codeEditorInstance}
					setEditorInstance={setCodeEditorInstance}
					{...actionsProps}
				/>
			</div>
		</>
	)
}
