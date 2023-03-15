import React, { Dispatch, SetStateAction, useEffect, useRef } from 'react'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { useSnippetsAPI } from '../../utils/api'
import { saveSnippet } from '../actions'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'

export interface CodeEditorProps extends SnippetInputProps {
	setEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ snippet, setSnippet, setEditorInstance }) => {
	const api = useSnippetsAPI(setSnippet)
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
				[`${controlKey}-S`]: () => saveSnippet(snippet, api),
				[`${controlKey}-Enter`]: () => saveSnippet(snippet, api)
			})

			return editor
		})
	}, [setEditorInstance, snippet, textareaRef, api])

	return snippet.id && 'condition' === snippet.scope ? null :
		<div className="snippet-editor" style={{ display: 'condition' === snippet.scope ? 'none' : 'block' }}>
			<textarea
				ref={textareaRef}
				id="snippet_code"
				name="snippet_code"
				rows={200}
				spellCheck={false}
				onChange={event => setSnippet(previous => ({ ...previous, code: event.target.value }))}
			>{snippet.code}</textarea>

			<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
		</div>
}
