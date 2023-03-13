import React, { Dispatch, Ref, SetStateAction, useEffect, useRef } from 'react'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'
import { CodeEditorInstance } from '../../types/editor'
import { saveSnippet } from '../actions'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'

export interface CodeEditorProps extends BaseSnippetProps {
	setEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ snippet, setSnippet, setEditorInstance }) => {
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setEditorInstance(instance => {
			const { codeEditor } = window.wp

			if (!textareaRef.current || instance) {
				return instance
			}

			const editor = codeEditor.initialize(textareaRef.current)

			const extraKeys = editor.codemirror.getOption('extraKeys')
			const controlKey = window.navigator.platform.match('Mac') ? 'Cmd' : 'Ctrl'

			editor.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : {},
				[`${controlKey}-S`]: () => saveSnippet(snippet),
				[`${controlKey}-Enter`]: () => saveSnippet(snippet)
			})

			return editor
		})
	}, [setEditorInstance, snippet, textareaRef])

	return snippet.id && 'condition' === snippet.scope ? null :
		<div className="snippet-editor" style={{ display: 'condition' === snippet.scope ? 'none' : 'block' }}>
			<textarea
				ref={textareaRef}
				id="snippet_code"
				name="snippet_code"
				rows={200}
				spellCheck={false}
				style={{ fontFamily: 'monospace', width: '100%' }}
				onChange={event => setSnippet(previous => ({ ...previous, code: event.target.value }))}
			>{snippet.code}</textarea>

			<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
		</div>
}
