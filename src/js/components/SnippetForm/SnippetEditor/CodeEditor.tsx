import React, { useEffect, useRef } from 'react'
import { isMacOS } from '../../../utils/general'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'

export const CodeEditor: React.FC = () => {
	const { snippet, setSnippet, codeEditorInstance, setCodeEditorInstance, submitSnippet } = useSnippetForm()
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setCodeEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance) {
				editorInstance = window.wp.codeEditor.initialize(textareaRef.current)

				editorInstance.codemirror.on('changes', instance => {
					setSnippet(previous => ({ ...previous, code: instance.getValue() }))
				})
			}

			return editorInstance
		})
	}, [setCodeEditorInstance, textareaRef, setSnippet])

	useEffect(() => {
		if (codeEditorInstance) {
			const extraKeys = codeEditorInstance.codemirror.getOption('extraKeys')
			const controlKey = isMacOS() ? 'Cmd' : 'Ctrl'

			codeEditorInstance.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : undefined,
				[`${controlKey}-S`]: submitSnippet,
				[`${controlKey}-Enter`]: submitSnippet
			})
		}
	}, [submitSnippet, codeEditorInstance, snippet])

	return (
		<div className="snippet-editor">
			<textarea
				ref={textareaRef}
				id="snippet_code"
				name="snippet_code"
				rows={200}
				spellCheck={false}
				onChange={event => {
					setSnippet(previous => ({ ...previous, code: event.target.value }))
				}}
			>{snippet.code}</textarea>

			<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
		</div>
	)
}
