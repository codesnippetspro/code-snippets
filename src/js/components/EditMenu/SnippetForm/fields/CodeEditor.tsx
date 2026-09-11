import React, { useEffect, useId, useRef } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { getEditorPhrases } from '../../../../editor/phrases'
import { createSnippetEditor, replaceEditorContent, setEditorSnippetType } from '../../../../editor/snippetEditor'
import { useSubmitSnippet } from '../../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../../utils/errors'
import { getSnippetType } from '../../../../utils/snippets/snippets'
import { stripWrapperTags } from '../../../../utils/snippets/tags'
import { useSnippetForm } from '../WithSnippetFormContext'
import { Button } from '../../../common/Button'
import { ExpandIcon } from '../../../common/icons/ExpandIcon'
import { MinimiseIcon } from '../../../common/icons/MinimiseIcon'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'
import type { ViewUpdate } from '@codemirror/view'
import type { Dispatch, SetStateAction } from 'react'
import type { SnippetCodeType, SnippetType } from '../../../../types/Snippet'

const toCodeType = (type: SnippetType): SnippetCodeType =>
	'cond' === type ? 'php' : type

interface EditorAreaProps {
	snippetCodeId: string
}

const PlainTextEditor: React.FC<EditorAreaProps> = ({ snippetCodeId }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<textarea
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
	)
}

/**
 * Keep the snippet in step with the editor's contents.
 *
 * Code pasted from a chat window or a file usually arrives wrapped in the tags
 * for its language. Those are removed here rather than silently on save, so the
 * editor shows what will actually be stored and does not flag an error for
 * markup we were going to strip anyway.
 */
const useEditorChangeHandler = () => {
	const { snippet, setSnippet, setCurrentNotice } = useSnippetForm()
	const snippetTypeRef = useRef(getSnippetType(snippet))

	useEffect(() => {
		snippetTypeRef.current = getSnippetType(snippet)
	}, [snippet])

	return useRef((update: ViewUpdate) => {
		let code = update.state.doc.toString()

		if (update.transactions.some(transaction => transaction.isUserEvent('input.paste'))) {
			const { code: stripped, removed } = stripWrapperTags(code, snippetTypeRef.current)

			if (removed) {
				code = stripped
				queueMicrotask(() => replaceEditorContent(update.view, stripped))
				setCurrentNotice(['updated', sprintf(
					/* translators: %s: markup that was removed, such as "opening PHP tag". */
					__('Removed the %s from the pasted code. Snippets do not need them.', 'code-snippets'),
					removed
				)])
			}
		}

		setSnippet(previous => ({ ...previous, code }))
	}).current
}

const CodeMirrorEditor: React.FC<EditorAreaProps> = ({ snippetCodeId }) => {
	const { snippet, editorView, setEditorView } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
	const containerRef = useRef<HTMLDivElement>(null)
	const initialSnippetRef = useRef(snippet)
	const saveRef = useRef<VoidFunction>(() => undefined)
	const handleChange = useEditorChangeHandler()
	const snippetType = toCodeType(getSnippetType(snippet))

	useEffect(() => {
		saveRef.current = () => {
			submitSnippet(snippet)
				.then(() => undefined)
				.catch(handleUnknownError)
		}
	}, [submitSnippet, snippet])

	useEffect(() => {
		const settings = window.CODE_SNIPPETS_EDITOR?.settings

		if (!containerRef.current || !settings) {
			return undefined
		}

		const view = createSnippetEditor({
			parent: containerRef.current,
			doc: initialSnippetRef.current.code,
			snippetType: toCodeType(getSnippetType(initialSnippetRef.current)),
			settings,
			surface: 'edit',
			phrases: getEditorPhrases(),
			contentAttributes: { 'id': snippetCodeId, 'aria-label': __('Snippet code', 'code-snippets') },
			onChange: handleChange,
			onSave: () => saveRef.current()
		})

		setEditorView(view)

		return () => {
			view.destroy()
			setEditorView(undefined)
		}
	}, [handleChange, setEditorView, snippetCodeId])

	useEffect(() => {
		if (editorView) {
			setEditorSnippetType(editorView, snippetType)
		}
	}, [editorView, snippetType])

	return <div ref={containerRef} />
}

const EditorArea: React.FC<EditorAreaProps> = ({ snippetCodeId }) => {
	const descriptionId = useId()
	const isEnabled = window.CODE_SNIPPETS_EDITOR?.enabled ?? false

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
			{isEnabled
				? <CodeMirrorEditor snippetCodeId={snippetCodeId} />
				: <PlainTextEditor snippetCodeId={snippetCodeId} />}
			{isEnabled && <CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDITOR?.settings.theme ?? 'default'} />}
		</div>
	)
}

export interface CodeEditorProps {
	isExpanded: boolean
	setIsExpanded: Dispatch<SetStateAction<boolean>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ isExpanded, setIsExpanded }) => {
	const snippetCodeId = useId()

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

			<EditorArea snippetCodeId={snippetCodeId} />
		</div>
	)
}
