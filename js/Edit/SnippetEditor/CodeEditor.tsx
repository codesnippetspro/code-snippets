import React, { Dispatch, SetStateAction, useEffect, useRef } from 'react'
import { SnippetActionsInputProps } from '../../types/SnippetInputProps'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { useSnippetActions } from '../actions'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'

export interface CodeEditorProps extends SnippetActionsInputProps {
	editorInstance: CodeEditorInstance | undefined
	setEditorInstance: Dispatch<SetStateAction<CodeEditorInstance | undefined>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({
	snippet,
	setSnippet,
	editorInstance,
	setEditorInstance,
	...actionsProps
}) => {
	const actions = useSnippetActions({ setSnippet, ...actionsProps })
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance) {
				editorInstance = window.wp.codeEditor.initialize(textareaRef.current)

				editorInstance.codemirror.on('changes', instance =>
					setSnippet(previous => ({ ...previous, code: instance.getValue() })))
			}

			return editorInstance
		})
	}, [setEditorInstance, textareaRef, setSnippet])

	useEffect(() => {
		if (editorInstance) {
			const extraKeys = editorInstance.codemirror.getOption('extraKeys')
			const controlKey = window.navigator.platform.match('Mac') ? 'Cmd' : 'Ctrl'
			const submitSnippet = () => actions.submit(snippet)

			editorInstance.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : undefined,
				[`${controlKey}-S`]: submitSnippet,
				[`${controlKey}-Enter`]: submitSnippet
			})
		}
	}, [actions, editorInstance, snippet])

	return (
		<div className="snippet-editor">
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
	)
}
