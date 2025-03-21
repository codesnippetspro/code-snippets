import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { handleUnknownError } from '../../../utils/errors'
import { isMacOS } from '../../../utils/conditions'
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
			const extraKeys = codeEditorInstance.codemirror.getOption('extraKeys') ?? {}
			const controlKey = isMacOS() ? 'Cmd' : 'Ctrl'
			const onSave = () => {
				submitSnippet()
					.then(() => undefined)
					.catch(handleUnknownError)
			}

			codeEditorInstance.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : undefined,
				[`${controlKey}-S`]: onSave,
				[`${controlKey}-Enter`]: onSave
			})
		}
	}, [submitSnippet, codeEditorInstance, snippet])

	return (
		<div className="snippet-code-container">
			<h2><label htmlFor="snippet-code">{__('Snippet Content', 'code-snippets')}</label></h2>

			<div className="snippet-editor">
				<textarea
					ref={textareaRef}
					id="snippet-code"
					name="snippet_code"
					rows={200}
					spellCheck={false}
					onChange={event => {
						setSnippet(previous => ({ ...previous, code: event.target.value }))
					}}
				>{snippet.code}</textarea>

				<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
			</div>
		</div>
	)
}
