import { __, _x } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { Editor, EditorConfiguration } from 'codemirror'
import React, { Dispatch, SetStateAction, useEffect } from 'react'
import { SnippetActionsInputProps, SnippetInputProps } from '../../types/SnippetInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { Snippet, SNIPPET_TYPE_SCOPES, SNIPPET_TYPES, SnippetType } from '../../types/Snippet'
import '../../editor'
import { getSnippetType, isLicensed, isProType } from '../../utils/snippets'
import classnames from 'classnames'
import { CodeEditor } from './CodeEditor'

interface SnippetTypeTabProps extends Pick<SnippetInputProps, 'setSnippet'> {
	tabType: SnippetType
	label: string
	currentType: SnippetType
	openUpgradeDialog: VoidFunction
}

const SnippetTypeTab: React.FC<SnippetTypeTabProps> = ({
	tabType,
	label,
	currentType,
	setSnippet,
	openUpgradeDialog
}) =>
	<a
		data-snippet-type={tabType}
		className={classnames({
			'nav-tab': true,
			'nav-tab-active': tabType === currentType,
			'nav-tab-inactive': isProType(tabType) && !isLicensed()
		})}
		{...isProType(tabType) && !isLicensed() ?
			{
				title: __('Learn more about Code Snippets Pro.', 'code-snippets'),
				href: 'https://codesnippets.pro/pricing/',
				target: '_blank',
				onClick: event => {
					event.preventDefault()
					openUpgradeDialog()
				}
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
	openUpgradeDialog: VoidFunction
}

const SnippetTypeTabs: React.FC<SnippetTypeTabsProps> = ({
	codeEditor,
	setSnippet,
	snippetType,
	openUpgradeDialog
}) => {

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
					openUpgradeDialog={openUpgradeDialog}
				/>)}

			{!isLicensed() ?
				<a
					className="button button-large nav-tab-button nav-tab-inactive go-pro-button"
					href="https://codesnippets.pro/pricing/"
					title="Find more about Pro"
					onClick={event => {
						event.preventDefault()
						openUpgradeDialog()
					}}
				>
					{_x('Upgrade to ', 'Upgrade to Pro', 'code-snippets')}
					<span className="badge">{_x('Pro', 'Upgrade to Pro', 'code-snippets')}</span>
				</a> :
				null}
		</h2>
	)
}

export interface SnippetEditorProps extends SnippetActionsInputProps {
	codeEditorInstance: CodeEditorInstance | undefined
	setCodeEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
	openUpgradeDialog: VoidFunction
}

export const SnippetEditor: React.FC<SnippetEditorProps> = ({
	snippet,
	setSnippet,
	codeEditorInstance,
	setCodeEditorInstance,
	openUpgradeDialog,
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
						openUpgradeDialog={openUpgradeDialog}
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
