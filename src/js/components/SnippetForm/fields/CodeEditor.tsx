import React, { useEffect, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useSubmitSnippet } from '../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../utils/errors'
import { isMacOS } from '../../../utils/screen'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { Button } from '../../common/Button'
import { ExpandIcon } from '../../common/icons/ExpandIcon'
import { MinimiseIcon } from '../../common/icons/MinimiseIcon'
import { CloudAIButton } from '../../EditorSidebar/actions/CloudAIButton'
import { ExplainSnippetButton } from './ExplainSnippetButton'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'
import type { LineWidget } from 'codemirror'
import type { Dispatch, RefObject, SetStateAction } from 'react'

const createWidgetElements = (message: string) => {
	const widgetEl = document.createElement('div')
	widgetEl.className = 'code-line-explanation'

	const icon = document.createElement('img')
	icon.setAttribute('src', `${window.CODE_SNIPPETS?.urls.plugin}/assets/generate.svg`)

	widgetEl.appendChild(icon)
	const messageEl = document.createElement('span')
	messageEl.appendChild(document.createTextNode(message))
	widgetEl.appendChild(messageEl)

	const actions = document.createElement('div')
	actions.className = 'code-line-actions'

	const commitBtn = document.createElement('div')
	commitBtn.className = 'action commit'
	commitBtn.title = __('Commit comment to code', 'code-snippets')
	commitBtn.appendChild(document.createTextNode('✓'))
	commitBtn.addEventListener('click', e => e.preventDefault())

	const removeBtn = document.createElement('div')
	removeBtn.className = 'action remove'
	removeBtn.title = __('Remove this comment', 'code-snippets')
	removeBtn.appendChild(document.createTextNode('✕'))
	removeBtn.addEventListener('click', e => e.preventDefault())

	actions.appendChild(commitBtn)
	actions.appendChild(removeBtn)
	widgetEl.appendChild(actions)

	return { widgetEl, commitBtn, removeBtn }
}

/**
 * Return a comment string for the given language type.
 * Supported types: php, css, js, html
 */
const getCommentForLanguage = (message: string, type = 'php') => {
	const text = String(message)
	switch ((type || '').toLowerCase()) {
		case 'css':
			return `/* ${text} */\n`
		case 'html':
			return `<!-- ${text} -->\n`
		case 'js':
		case 'javascript':
			return `// ${text}\n`
		case 'php':
		default:
			// Use single-line PHP/JS-style comment for php by default
			return `// ${text}\n`
	}
}

const extractSnippetLanguage = (snippetObj: unknown): string => {
	if (!snippetObj || 'object' !== typeof snippetObj) {
		return 'php'
	}

	const record = snippetObj as Record<string, unknown>
	const candidate = record.type ?? record.language
	if ('string' === typeof candidate) {
		const cand = String(candidate)
		if (0 < cand.length) {
			return cand
		}
	}

	return 'php'
}

/**
 * Get the indentation (leading tabs/spaces) for a given line index in the doc.
 * If the line is empty, search upward for the nearest non-empty line and use
 * its indentation. Returns an empty string when none found or on error.
 */
const getIndentForLine = (doc: unknown, lineIndex: number): string => {
	if (!doc || 'object' !== typeof doc) {
		return ''
	}

	// Narrow doc to an object that at least provides getLine.
	const d = doc as { getLine: (n: number) => string }
	const safeLine = Math.max(0, lineIndex)

	try {
		const targetText = String(d.getLine(safeLine) || '')
		if ('' === targetText.trim() && 0 < safeLine) {
			let p = safeLine - 1
			while (0 <= p) {
				const prev = String(d.getLine(p) || '')
				if ('' !== prev.trim()) {
					const m = /^[\t ]*/.exec(prev)
					return m ? m[0] : ''
				}
				p -= 1
			}

			return ''
		}

		const m = /^[\t ]*/.exec(targetText)
		return m ? m[0] : ''
	} catch (_err) {
		return ''
	}
}

interface EditorTextareaProps {
	textareaRef: RefObject<HTMLTextAreaElement>
}

const EditorTextarea: React.FC<EditorTextareaProps> = ({ textareaRef }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<div className="snippet-editor">
			<textarea
				ref={textareaRef}
				id="snippet-code"
				name="snippet_code"
				value={snippet.code}
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

const ExplainCodeButton: React.FC = () => {
	const { codeEditorInstance, snippet } = useSnippetForm()
	const [, setWidgets] = useState<LineWidget[]>([])

	return (
		<ExplainSnippetButton
			field="code"
			title={__('Explain this snippet with AI.', 'code-snippets')}
			onRequest={() => {
				setWidgets(widgets => {
					widgets.forEach(widget => widget.clear())
					return []
				})
			}}
			onResponse={generated => {
				const doc = codeEditorInstance?.codemirror.getDoc()
				console.info('lines', generated.lines)

				setWidgets(() => {
					if (!doc || !generated.lines) {
						return []
					}
					const entries = Object.entries(generated.lines ?? {}) as [string, string][]

					return entries.map(([line, message]: [string, string]) => {
						const lineNumber = parseInt(line, 10) - 1

						const { widgetEl, commitBtn, removeBtn } = createWidgetElements(message)

						const lineWidget = doc.addLineWidget(lineNumber, widgetEl, { above: true })

						commitBtn.addEventListener('click', () => {
							const language = extractSnippetLanguage(snippet)
							const rawComment = getCommentForLanguage(message, language)

							const safeLine = Math.max(0, lineNumber)
							const indent = getIndentForLine(doc, safeLine)
							const comment = `${indent}${rawComment}`
							doc.replaceRange(comment, { line: safeLine, ch: 0 })

							lineWidget.clear()
							setWidgets(prev => prev.filter(w => w !== lineWidget))
						})

						removeBtn.addEventListener('click', () => {
							lineWidget.clear()
							setWidgets(prev => prev.filter(w => w !== lineWidget))
						})

						return lineWidget
					})
				})
			}}
		>
			{__('Explain', 'code-snippets')}
		</ExplainSnippetButton>
	)
}

interface GenerateCodeButtonProps {
	setShowCreateModal: Dispatch<SetStateAction<boolean>>
}

const GenerateCodeButton: React.FC<GenerateCodeButtonProps> = ({ setShowCreateModal }) => {
	const { snippet, isWorking, isReadOnly } = useSnippetForm()

	return (
		<CloudAIButton
			primary={0 === snippet.id}
			snippet={snippet}
			disabled={isWorking || isReadOnly}
			title={__('Generate a new snippet with AI.', 'code-snippets')}
			onClick={() => setShowCreateModal(true)}
		>
			{'' === snippet.code.trim()
				? __('Generate', 'code-snippets')
				: __('Generate New', 'code-snippets')}
		</CloudAIButton>
	)
}

export interface CodeEditorProps {
	isExpanded: boolean
	setIsExpanded: Dispatch<SetStateAction<boolean>>
	setIsGenerateModalOpen: Dispatch<SetStateAction<boolean>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ isExpanded, setIsExpanded, setIsGenerateModalOpen }) => {
	const { snippet, setSnippet, codeEditorInstance, setCodeEditorInstance } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
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
			<div className="above-snippet-code">
				<h2><label htmlFor="snippet-code">{__('Snippet Content', 'code-snippets')}</label></h2>

				<Button small className="expand-editor-button" onClick={() => setIsExpanded(current => !current)}>
					{isExpanded ? <MinimiseIcon /> : <ExpandIcon />}
					{isExpanded ? __('Minimize', 'code-snippets') : __('Expand', 'code-snippets')}
				</Button>

				<ExplainCodeButton />
				<GenerateCodeButton setShowCreateModal={setIsGenerateModalOpen} />
			</div>

			<EditorTextarea textareaRef={textareaRef} />
		</div>
	)
}
