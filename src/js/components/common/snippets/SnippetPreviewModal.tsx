import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useEffect, useRef, useState } from 'react'
import { getEditorPhrases } from '../../../editor/phrases'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadSnippetExportFile } from '../../../utils/files'
import { canModifySnippet, cloneSnippetObject, getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets/snippets'
import { Badge } from '../Badge'
import { Button } from '../Button'
import { CloudSnippetDownloadButton } from '../cloud/CloudSnippetDownloadButton'
import { ConfirmDeleteDialog, useDeleteSnippet } from './ConfirmDeleteDialog'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { EditorView } from '@codemirror/view'
import type { ReactNode } from 'react'
import type { Snippet, SnippetCodeType, SnippetType } from '../../../types/Snippet'

const getClipboard = (): Clipboard | undefined =>
	window.isSecureContext ? navigator.clipboard as Clipboard | undefined : undefined

/**
 * PHP code is previewed with its opening tag, so it is parsed as a template
 * rather than as the bare code a PHP snippet's editor holds.
 */
const getPreviewLanguage = (type: SnippetType): SnippetCodeType =>
	'css' === type || 'js' === type ? type : 'html'

const getPreviewCode = (type: SnippetType, code: string): string =>
	`${'php' === type ? '<?php\n\n' : ''}${code}`

/**
 * Show snippet code in a read-only editor. The editor is loaded when a preview
 * is first opened, so the snippets list does not pay for it up front.
 */
const usePreviewEditor = (type: SnippetType, code: string) => {
	const containerRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		const container = containerRef.current
		const settings = window.CODE_SNIPPETS_EDITOR?.settings

		if (!container || !settings) {
			return undefined
		}

		let view: EditorView | undefined
		let isCancelled = false

		// The modal scales in as it opens. The editor measures itself against that
		// scale, and a transform does not resize anything that would prompt it to
		// measure again, so its cursor, selection and highlight layers would stay
		// scaled once the animation ends.
		const modalFrame = container.closest('.components-modal__frame')
		const remeasure = () => view?.requestMeasure()
		modalFrame?.addEventListener('animationend', remeasure)

		import(/* webpackChunkName: "snippet-editor" */ '../../../editor/snippetEditor.js')
			.then(({ createSnippetEditor }) => {
				if (!isCancelled) {
					view = createSnippetEditor({
						parent: container,
						doc: getPreviewCode(type, code),
						snippetType: getPreviewLanguage(type),
						settings: { ...settings, lineNumbers: true },
						surface: 'preview',
						readOnly: true,
						phrases: getEditorPhrases(),
						contentAttributes: { 'aria-label': __('Snippet code preview', 'code-snippets') }
					})
				}
			})
			.catch(handleUnknownError)

		return () => {
			isCancelled = true
			modalFrame?.removeEventListener('animationend', remeasure)
			view?.destroy()
		}
	}, [type, code])

	return containerRef
}

/**
 * Tracks whether a footer action is in flight. The ref mirrors the state so
 * `beginWorking` can reject re-entry within the same tick, before React
 * re-renders with the disabled buttons.
 */
const useWorkingState = () => {
	const [isWorking, setIsWorking] = useState(false)
	const isWorkingRef = useRef(false)
	const updateWorking = (value: boolean) => {
		isWorkingRef.current = value
		setIsWorking(value)
	}

	return { isWorking, setIsWorking: updateWorking }
}

enum CopyStatus { Ready, Copied, Failed}

const CopyCodeButton: React.FC<{ code: string }> = ({ code }) => {
	const [copyStatus, setCopyStatus] = useState(CopyStatus.Ready)

	const handleCopy = () => {
		const clipboard = getClipboard()

		if (!clipboard) {
			setCopyStatus(CopyStatus.Failed)
			return
		}

		void clipboard.writeText(code)
			.then(() => setCopyStatus(CopyStatus.Copied))
			.catch(() => setCopyStatus(CopyStatus.Failed))
	}

	return (
		<Button secondary onClick={handleCopy}>
			{(() => {
				switch (copyStatus) {
					case CopyStatus.Copied:
						return __('Copied', 'code-snippets')
					case CopyStatus.Failed:
						return __('Copy unavailable', 'code-snippets')
					case CopyStatus.Ready:
						return __('Copy code', 'code-snippets')
				}
			})()}
		</Button>
	)
}

export interface PreviewModalProps {
	onRequestClose: VoidFunction
	title: string
	type: SnippetType
	code: string
	children?: ReactNode
}

export const PreviewModal: React.FC<PreviewModalProps> = ({ onRequestClose, title, type, code, children }) => {
	const editorRef = usePreviewEditor(type, code)
	const isEditorEnabled = window.CODE_SNIPPETS_EDITOR?.enabled ?? false

	return (
		<Modal
			className="code-snippets-preview-modal"
			onRequestClose={onRequestClose}
			title={title}
			headerActions={
				<div className="code-snippets-preview-modal__badge">
					<Badge name={type} />
				</div>
			}
		>
			{isEditorEnabled
				? <div className="code-snippets-preview-modal__editor" ref={editorRef} />
				: <div className="code-snippets-preview-modal__editor">
					<textarea
						readOnly
						aria-label={__('Snippet code preview', 'code-snippets')}
						defaultValue={getPreviewCode(type, code)}
					/>
				</div>}
			{children}
		</Modal>
	)
}

export interface SnippetCodePreviewModalProps {
	snippet: CloudSnippetSchema
	setIsOpen: (isOpen: boolean) => void
	onDownloaded: VoidFunction
}

export const CloudSnippetPreviewModal: React.FC<SnippetCodePreviewModalProps> = ({
	snippet,
	setIsOpen,
	onDownloaded
}) => {
	return (
		<PreviewModal
			code={snippet.code}
			type={getSnippetType(snippet)}
			title={snippet.name}
			onRequestClose={() => setIsOpen(false)}
		>
			<div className="code-snippets-preview-modal__footer">
				<div className="code-snippets-preview-modal__buttons">
					<CloudSnippetDownloadButton snippet={snippet} onDownloaded={onDownloaded} />
					{getClipboard() && <CopyCodeButton code={snippet.code} />}
				</div>
			</div>
		</PreviewModal>
	)
}

interface ActionButtonProps {
	snippet: Snippet
	isWorking: boolean
	setIsWorking: (isWorking: boolean) => void
}

interface CloneButtonProps extends ActionButtonProps {
	setIsOpen: (isOpen: boolean) => void
}

const CloneButton: React.FC<CloneButtonProps> = ({ snippet, isWorking, setIsWorking, setIsOpen }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const handleClone = () => {
		setIsWorking(true)

		api.create(cloneSnippetObject(snippet))
			.then(refreshSnippetsList)
			.then(() => setIsOpen(false))
			.catch(handleUnknownError)
			.finally(() => setIsWorking(false))
	}

	return (
		<Button secondary disabled={isWorking} onClick={handleClone}>
			{__('Clone', 'code-snippets')}
		</Button>
	)
}

const ExportButton: React.FC<ActionButtonProps> = ({ snippet, isWorking, setIsWorking }) => {
	const api = useSnippetsAPI()

	const handleExport = () => {
		setIsWorking(true)

		api.export(snippet)
			.then(response => downloadSnippetExportFile(response, snippet))
			.catch(handleUnknownError)
			.finally(() => setIsWorking(false))
	}

	return (
		<Button
			secondary
			disabled={isWorking}
			onClick={handleExport}
		>
			{__('Export', 'code-snippets')}
		</Button>
	)
}

export interface SnippetPreviewModalProps {
	snippet: Snippet
	setIsOpen: (open: boolean) => void
}

export const SnippetPreviewModal: React.FC<SnippetPreviewModalProps> = ({ snippet, setIsOpen }) => {
	const { refreshSnippetsList } = useSnippetsList()
	const { isWorking, setIsWorking } = useWorkingState()

	const { requestDelete, deleteDialogProps } = useDeleteSnippet({
		snippet,
		setIsWorking,
		onSuccess: () => {
			setIsOpen(false)
			return refreshSnippetsList()
		},
		onError: handleUnknownError
	})

	const canModify = canModifySnippet(snippet)

	return (
		<PreviewModal
			code={snippet.code}
			type={getSnippetType(snippet)}
			title={getSnippetDisplayName(snippet)}
			onRequestClose={() => setIsOpen(false)}
		>
			<div className="code-snippets-preview-modal__footer">
				<div className="code-snippets-preview-modal__buttons">
					<a className="button button-primary" href={getSnippetEditUrl(snippet)}>
						{snippet.locked || !canModify
							? __('View', 'code-snippets')
							: __('Edit', 'code-snippets')}
					</a>

					{canModify && <CloneButton snippet={snippet} isWorking={isWorking} setIsWorking={setIsWorking} setIsOpen={setIsOpen} />}

					<ExportButton snippet={snippet} isWorking={isWorking} setIsWorking={setIsWorking} />
					<CopyCodeButton code={snippet.code} />

					{!snippet.locked && canModify && (
						<Button
							link
							className="code-snippets-preview-modal__trash"
							disabled={isWorking}
							onClick={() => void requestDelete()}
						>
							{__('Trash', 'code-snippets')}
						</Button>)}
				</div>

				<div className="code-snippets-preview-modal__priority">
					<span>{__('Priority', 'code-snippets')}</span>
					<span className="code-snippets-preview-modal__priority-value">{snippet.priority}</span>
				</div>

				<ConfirmDeleteDialog {...deleteDialogProps} />
			</div>
		</PreviewModal>
	)
}
