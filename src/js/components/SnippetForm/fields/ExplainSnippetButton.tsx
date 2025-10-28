import { Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import React, { useState } from 'react'
import { useGenerativeAPI } from '../../../hooks/useGenerativeAPI'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { isCondition } from '../../../utils/snippets/snippets'
import { trimTrailingChar } from '../../../utils/text'
import { Tooltip } from '../../common/Tooltip'
import { CloudAIButton } from '../../EditorSidebar/actions/CloudAIButton'
import type { ButtonProps } from '../../common/Button'
import type { ExplainSnippetFields, ExplainedSnippet } from '../../../hooks/useGenerativeAPI'
import type { LineHandle, LineWidget } from 'codemirror'
import type { Dispatch, SetStateAction } from 'react'

interface DocumentLike { getLine: (n: number) => string | undefined }

interface CodeMirrorDoc {
	getLine(n: number): string
	getLineNumber(handle: LineHandle): number | null
	addLineWidget(line: number, node: HTMLElement, opts?: Record<string, unknown>): LineWidget
	replaceRange(text: string, pos: { line: number; ch: number }): void
}

interface CodeEditorInstance {
	codemirror: {
		getDoc: () => CodeMirrorDoc
	}
}

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

const isDocumentLike = (doc: unknown): doc is DocumentLike =>
	'object' === typeof doc && null !== doc && 'function' === typeof (doc as DocumentLike).getLine

const INDENT_REGEX = /^[\t ]*/

export const getIndentForLine = (doc: unknown, lineIndex: number): string => {
	if (!isDocumentLike(doc)) {return ''}

	const lineNumber = Math.max(0, lineIndex)
	const getLineText = (index: number): string => doc.getLine(index) ?? ''

	try {
		const currentLine = getLineText(lineNumber)

		// If the current line is blank (only whitespace), look upwards for the nearest non-blank line
		if (!currentLine.trim() && 0 < lineNumber) {
			return findIndentFromPreviousLines(lineNumber, getLineText)
		}

		// Otherwise, return the indentation of the current line
		return extractIndent(currentLine)
	} catch {
		return ''
	}
}

const findIndentFromPreviousLines = (
	startIndex: number,
	getLineText: (index: number) => string
): string => {
	for (let i = startIndex - 1; 0 <= i; i--) {
		const previousLine = getLineText(i)
		if (previousLine.trim()) {
			return extractIndent(previousLine)
		}
	}
	return ''
}

const extractIndent = (line: string): string => INDENT_REGEX.exec(line)?.[0] ?? ''

const processExplainResponse = (
	response: ExplainedSnippet,
	snippet: unknown,
	codeEditorInstance: CodeEditorInstance | undefined,
	setWidgets: Dispatch<SetStateAction<LineWidget[]>>
) => {
	try {
		const doc = codeEditorInstance?.codemirror.getDoc()
		if (!doc || !response.lines) {
			return
		}

		const entries = Object.entries(response.lines) as [string, string][]
		const widgets = entries.map(([line, message]: [string, string]) => {
			const lineNumber = parseInt(line, 10) - 1
			const { widgetEl, commitBtn, removeBtn } = createWidgetElements(message)

			const lineWidget = doc.addLineWidget(lineNumber, widgetEl, { above: true })

			commitBtn.addEventListener('click', () => {
				// Get the widget's current line position, which updates as other insertions happen
				const widgetWithLine = lineWidget as LineWidget & { line?: LineHandle }
				const widgetLine = widgetWithLine.line
				const currentLineNumber = widgetLine ? doc.getLineNumber(widgetLine) : null

				if (null === currentLineNumber) {
					return
				}

				const language = extractSnippetLanguage(snippet)
				const rawComment = getCommentForLanguage(message, language)

				const safeLine = Math.max(0, currentLineNumber)
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

		setWidgets(widgets)
	} catch (_err) {
		// Continue silently
	}
}

const handleExplainClick = async (params: {
	snippet: unknown
	field: ExplainSnippetFields
	explainSnippet: (code: string, field: ExplainSnippetFields) => Promise<ExplainedSnippet>
	codeEditorInstance?: CodeEditorInstance
	onRequest?: VoidFunction
	onResponse?: (generated: ExplainedSnippet) => void
	setIsWorking: (v: boolean) => void
	setErrorMessage: (m?: string) => void
	setWidgets: Dispatch<SetStateAction<LineWidget[]>>
}) => {
	const { snippet, field, explainSnippet, codeEditorInstance, onRequest, onResponse, setIsWorking, setErrorMessage, setWidgets } = params

	setIsWorking(true)
	setErrorMessage(undefined)
	onRequest?.()

	try {
		const code = (snippet as { code?: string }).code ?? ''
		const response = await explainSnippet(code, field)
		setIsWorking(false)

		processExplainResponse(response, snippet, codeEditorInstance, setWidgets)
		onResponse?.(response)
	} catch (error: unknown) {
		setIsWorking(false)
		if (isAxiosError(error)) {
			// Axios error is narrowed by isAxiosError
			setErrorMessage((error as unknown as { message?: string }).message)
		} else {
			setErrorMessage(__('An unknown error occurred.', 'code-snippets'))
		}
	}
}


export interface ExplainSnippetButtonProps extends Omit<ButtonProps, 'onClick'> {
	field: ExplainSnippetFields
	onRequest?: VoidFunction
	onResponse?: (generated: ExplainedSnippet) => void
}

export const ExplainSnippetButton: React.FC<ExplainSnippetButtonProps> = ({
	field,
	onRequest,
	onResponse,
	...buttonProps
}) => {
	const { snippet, isReadOnly, codeEditorInstance } = useSnippetForm()
	const [isWorking, setIsWorking] = useState(false)
	const [errorMessage, setErrorMessage] = useState<string>()
	const { explainSnippet } = useGenerativeAPI()
	const [, setWidgets] = useState<LineWidget[]>([])

	return '' !== snippet.code.trim() || isCondition(snippet)
		? <div className="generate-button">
			{isWorking ? <Spinner /> : null}

			{errorMessage
				? <Tooltip block end icon={<span className="dashicons dashicons-warning"></span>}>
					{`${trimTrailingChar(errorMessage, '.')}. ${__('Please try again.', 'code-snippets')}`}
				</Tooltip>
				: null}

			<CloudAIButton
				snippet={snippet}
				disabled={isReadOnly || isWorking}
				{...buttonProps}
				onClick={() => {
					void handleExplainClick({
						snippet,
						field,
						explainSnippet,
						codeEditorInstance,
						onRequest,
						onResponse,
						setIsWorking,
						setErrorMessage,
						setWidgets
					})
				}}
			/>
		</div>
		: null
}
