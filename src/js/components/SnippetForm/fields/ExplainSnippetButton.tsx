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
import type { LineWidget } from 'codemirror'

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

const getIndentForLine = (doc: unknown, lineIndex: number): string => {
	if (!doc || 'object' !== typeof doc) {
		return ''
	}

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

const processExplainResponse = (
	response: ExplainedSnippet,
	snippet: unknown,
	codeEditorInstance: unknown,
	setWidgets: (w: LineWidget[]) => void
) => {
	try {
		const doc = (codeEditorInstance as any)?.codemirror?.getDoc()
		if (!doc || !response.lines) {
			return
		}

		const entries = Object.entries(response.lines) as [string, string][]
		const widgets = entries.map(([line, message]: [string, string]) => {
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

		setWidgets(widgets)
	} catch (_err) {
		// Continue silently
	}
}

const handleExplainClick = async (params: {
	snippet: unknown
	field: ExplainSnippetFields
	explainSnippet: (code: string, field: ExplainSnippetFields) => Promise<ExplainedSnippet>
	codeEditorInstance: unknown
	onRequest?: VoidFunction
	onResponse?: (generated: ExplainedSnippet) => void
	setIsWorking: (v: boolean) => void
	setErrorMessage: (m?: string) => void
	setWidgets: (w: LineWidget[]) => void
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
		if (isAxiosError(error) && 'message' in error) {
			setErrorMessage((error as any).message)
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

	// Module-scope helper functions are used; nothing additional needed here

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
