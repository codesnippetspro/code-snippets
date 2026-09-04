import React, { useEffect, useId, useRef } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'
import { isMacOS } from '../../../../utils/screen'
import { getSnippetType } from '../../../../utils/snippets/snippets'
import { stripWrapperTags } from '../../../../utils/snippets/tags'
import { useSnippetForm } from '../WithSnippetFormContext'
import { Button } from '../../../common/Button'
import { ExpandIcon } from '../../../common/icons/ExpandIcon'
import { MinimiseIcon } from '../../../common/icons/MinimiseIcon'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'
import type { Dispatch, RefObject, SetStateAction } from 'react'
import type { ScreenNotice } from '../../../../types/ScreenNotice'
import type { Snippet } from '../../../../types/Snippet'

interface EditorTextareaProps {
	textareaRef: RefObject<HTMLTextAreaElement>
	snippetCodeId: string
}

const EditorTextarea: React.FC<EditorTextareaProps> = ({ textareaRef, snippetCodeId }) => {
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
				id={snippetCodeId}
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

/**
 * Keep the editor's contents in step with the snippet being edited.
 *
 * Code pasted from a chat window or a file usually arrives wrapped in the tags
 * for its language. Those are removed here rather than silently on save, so the
 * editor shows what will actually be stored and does not flag an error for
 * markup we were going to strip anyway.
 */
const handleEditorChanges = (
	instance: CodeMirror.Editor,
	changes: readonly CodeMirror.EditorChange[],
	setSnippet: Dispatch<SetStateAction<Snippet>>,
	setCurrentNotice: Dispatch<SetStateAction<ScreenNotice | undefined>>
) => {
	const pasted = changes.some(change => 'paste' === change.origin)

	setSnippet(previous => {
		const value = instance.getValue()

		if (!pasted) {
			return { ...previous, code: value }
		}

		const { code, removed } = stripWrapperTags(value, getSnippetType(previous))

		if (removed) {
			instance.setValue(code)
			setCurrentNotice(['updated', sprintf(
				/* translators: %s: markup that was removed, such as "opening PHP tag". */
				__('Removed the %s from the pasted code. Snippets do not need them.', 'code-snippets'),
				removed
			)])
		}

		return { ...previous, code }
	})
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ isExpanded, setIsExpanded }) => {
	const { snippet, setSnippet, codeEditorInstance, setCodeEditorInstance, setCurrentNotice } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
	const textareaRef = useRef<HTMLTextAreaElement>(null)
	const snippetCodeId = useId()

	useEffect(() => {
		setCodeEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance && window.wp.codeEditor) {
				editorInstance = window.wp.codeEditor.initialize(textareaRef.current)

				// CodeMirror hides the labelled textarea and types into an unlabelled one
				// of its own, so the name has to be put on that input directly.
				editorInstance.codemirror.getInputField().setAttribute('aria-label', __('Snippet code', 'code-snippets'))

				editorInstance.codemirror.on('changes', (instance, changes) =>
					handleEditorChanges(instance, changes, setSnippet, setCurrentNotice))
			}

			return editorInstance
		})
	}, [setCodeEditorInstance, textareaRef, setSnippet, setCurrentNotice])

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

	return (
		<div className="snippet-code-container">
			<div className="above-snippet-code">
				<label htmlFor={snippetCodeId}>
					{__('Snippet Content', 'code-snippets')}
				</label>

				<Button small className="expand-editor-button" onClick={() => setIsExpanded(current => !current)}>
					{isExpanded ? <MinimiseIcon aria-hidden="true" /> : <ExpandIcon aria-hidden="true" />}
					{isExpanded ? __('Minimize', 'code-snippets') : __('Expand', 'code-snippets')}
				</Button>
			</div>

			<EditorTextarea textareaRef={textareaRef} snippetCodeId={snippetCodeId} />
		</div>
	)
}
