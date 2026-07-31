import React, { useEffect, useId, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'
import { isMacOS } from '../../../../utils/screen'
import { useSnippetForm } from '../WithSnippetFormContext'
import { Button } from '../../../common/Button'
import { ExpandIcon } from '../../../common/icons/ExpandIcon'
import { MinimiseIcon } from '../../../common/icons/MinimiseIcon'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'
import type { Dispatch, RefObject, SetStateAction } from 'react'

interface EditorTextareaProps {
	textareaRef: RefObject<HTMLTextAreaElement>
}

const useFocusEditorShortcut = (
	textareaRef: RefObject<HTMLTextAreaElement>
) => {
	const { codeEditorInstance } = useSnippetForm()

	useEffect(() => {
		const focusEditor = () => {
			if (codeEditorInstance) {
				codeEditorInstance.codemirror.focus()
				return
			}

			textareaRef.current?.focus()
		}

		window.addEventListener('code_snippets_focus_editor', focusEditor)

		return () => {
			window.removeEventListener('code_snippets_focus_editor', focusEditor)
		}
	}, [codeEditorInstance, textareaRef])
}

const EditorTextarea: React.FC<EditorTextareaProps> = ({ textareaRef }) => {
	const descriptionId = useId()
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<div
			className="snippet-editor"
			role="application"
			aria-label={__('Code editor', 'code-snippets')}
			aria-describedby={descriptionId}
		>
			<p id={descriptionId} className="screen-reader-text">
				{__('In the editing area, the Tab key enters a tab character. To exit the code editor, press the Escape key and then the Tab key.', 'code-snippets')}
			</p>
			<textarea
				ref={textareaRef}
				id="snippet-code"
				name="snippet_code"
				value={snippet.code}
				aria-label={__('Snippet code', 'code-snippets')}
				rows={200}
				spellCheck={false}
				onChange={event => {
					setSnippet(previous => ({ ...previous, code: event.target.value }))
				}}
			/>
			<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
		</div>
	)
}

export interface CodeEditorProps {
	isExpanded: boolean
	setIsExpanded: Dispatch<SetStateAction<boolean>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ isExpanded, setIsExpanded }) => {
	const { snippet, setSnippet, codeEditorInstance, setCodeEditorInstance } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setCodeEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance && window.wp.codeEditor) {
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
				submitSnippet(snippet)
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

	useFocusEditorShortcut(textareaRef)

	return (
		<div className="snippet-code-container">
			<div className="above-snippet-code">
				<label htmlFor="snippet-code">
					{__('Snippet Content', 'code-snippets')}
				</label>

				<Button small className="expand-editor-button" onClick={() => setIsExpanded(current => !current)}>
					{isExpanded ? <MinimiseIcon aria-hidden="true" /> : <ExpandIcon aria-hidden="true" />}
					{isExpanded ? __('Minimize', 'code-snippets') : __('Expand', 'code-snippets')}
				</Button>
			</div>

			<EditorTextarea textareaRef={textareaRef} />
		</div>
	)
}
