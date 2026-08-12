import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useEffect, useRef, useState } from 'react'
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
import type { EditorConfiguration, EditorFromTextArea } from 'codemirror'
import type { ReactNode } from 'react'
import type { Snippet, SnippetType } from '../../../types/Snippet'

const EDITOR_MODES: Record<string, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

const getClipboard = (): Clipboard | undefined =>
	window.isSecureContext ? navigator.clipboard as Clipboard | undefined : undefined

const getPreviewEditorSettings = (type: string): EditorConfiguration => ({
	extraKeys: {
		'Tab': false,
		'Shift-Tab': false
	},
	readOnly: true,
	lineNumbers: true,
	theme: window.CODE_SNIPPETS_MANAGE?.editorTheme ?? 'default',
	mode: EDITOR_MODES[type] ?? EDITOR_MODES.php,
	screenReaderLabel: __('Snippet code preview', 'code-snippets')
})

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

interface PreviewModalProps {
	onRequestClose: VoidFunction
	title: string
	type: SnippetType
	code: string
	children: ReactNode
}

const PreviewModal: React.FC<PreviewModalProps> = ({ onRequestClose, title, type, code, children }) => {
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		if (!textareaRef.current || !window.wp.codeEditor) {
			return undefined
		}

		const instance = window.wp.codeEditor.initialize(
			textareaRef.current,
			{ codemirror: getPreviewEditorSettings(type) }
		)

		// CodeMirror hides the labeled source textarea and creates an unlabelled
		// internal input. The screenReaderLabel option only exists from CodeMirror
		// 5.59, while WordPress 5.5 ships 5.29, so label the input directly.
		instance.codemirror.getInputField().setAttribute('aria-label', __('Snippet code preview', 'code-snippets'))

		return () => {
			(instance.codemirror as EditorFromTextArea).toTextArea()
		}
	}, [type])

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
			<div className="code-snippets-preview-modal__editor">
				<textarea
					ref={textareaRef}
					readOnly
					aria-label={__('Snippet code preview', 'code-snippets')}
					defaultValue={`${'php' === type ? '<?php\n\n' : ''}${code}`}
				/>
			</div>
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
