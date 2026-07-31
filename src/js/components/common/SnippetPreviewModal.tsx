import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useEffect, useRef, useState } from 'react'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { handleUnknownError } from '../../utils/errors'
import { downloadSnippetExportFile } from '../../utils/files'
import { canModifySnippet, cloneSnippetObject, getSnippetEditUrl } from '../../utils/snippets/snippets'
import { Badge } from './Badge'
import { Button } from './Button'
import { useDeleteSnippet } from './DeleteButton'
import type { EditorConfiguration, EditorFromTextArea } from 'codemirror'
import type { PropsWithChildren, ReactNode } from 'react'
import type { Snippet, SnippetType } from '../../types/Snippet'

const CODE_PREVIEW_LABEL = __('Snippet code preview', 'code-snippets')

export interface SnippetPreviewModalProps {
	title: string
	code: string
	type: SnippetType
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
	snippet?: Snippet
	footerActions?: ReactNode
}

const EDITOR_MODES: Record<string, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

const getPreviewEditorSettings = (type: string): EditorConfiguration => ({
	extraKeys: {
		'Tab': false,
		'Shift-Tab': false
	},
	readOnly: true,
	lineNumbers: true,
	theme: window.CODE_SNIPPETS_MANAGE?.editorTheme ?? 'default',
	mode: EDITOR_MODES[type] ?? EDITOR_MODES.php,
	screenReaderLabel: CODE_PREVIEW_LABEL
})

interface SnippetPreviewActionsProps {
	snippet: Snippet
	closeModal: () => void
}

interface SnippetPreviewButtonsProps extends SnippetPreviewActionsProps {
	requestDelete: () => void
	isWorking: boolean
	setIsWorking: (working: boolean) => void
}

const usePreviewActionHandlers = ({
	snippet,
	closeModal,
	setIsWorking
}: Omit<SnippetPreviewButtonsProps, 'requestDelete' | 'isWorking'>) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const handleClone = () => {
		setIsWorking(true)

		api.create(cloneSnippetObject(snippet))
			.then(refreshSnippetsList)
			.then(closeModal)
			.catch(handleUnknownError)
			.finally(() => setIsWorking(false))
	}
	const handleExport = () => {
		setIsWorking(true)

		api.export(snippet)
			.then(response => downloadSnippetExportFile(response, snippet))
			.catch(handleUnknownError)
			.finally(() => setIsWorking(false))
	}

	return { handleClone, handleExport }
}

const SnippetPreviewButtons: React.FC<SnippetPreviewButtonsProps> = ({
	snippet,
	closeModal,
	requestDelete,
	isWorking,
	setIsWorking
}) => {
	const canModify = canModifySnippet(snippet)
	const actionOptions = { snippet, closeModal, setIsWorking }
	const { handleClone, handleExport } = usePreviewActionHandlers(actionOptions)

	return (
		<div className="code-snippets-preview-modal__buttons">
			<a className="button button-primary" href={getSnippetEditUrl(snippet)}>
				{snippet.locked || !canModify ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
			</a>

			{canModify
				? <Button
					secondary
					disabled={isWorking}
					onClick={handleClone}
				>
					{__('Clone', 'code-snippets')}
				</Button>
				: null}

			<Button
				secondary
				disabled={isWorking}
				onClick={handleExport}
			>
				{__('Export', 'code-snippets')}
			</Button>

			{snippet.locked || !canModify
				? null
				: <Button
					link
					className="code-snippets-preview-modal__trash"
					disabled={isWorking}
					onClick={requestDelete}
				>
					{__('Trash', 'code-snippets')}
				</Button>}
		</div>
	)
}

/**
 * Footer action bar for previews of local snippets. Requires the snippets API
 * and snippets list contexts, so it is only rendered when the modal receives a
 * full snippet object rather than bare title/code/type values.
 */
const SnippetPreviewActions: React.FC<SnippetPreviewActionsProps> = ({
	snippet,
	closeModal
}) => {
	const { refreshSnippetsList } = useSnippetsList()
	const [isWorking, setIsWorking] = useState(false)
	const { requestDelete, confirmDialog } = useDeleteSnippet({
		snippet,
		setIsWorking,
		onSuccess: () => {
			closeModal()
			return refreshSnippetsList()
		},
		onError: handleUnknownError
	})

	return (
		<div className="code-snippets-preview-modal__footer">
			<SnippetPreviewButtons
				snippet={snippet}
				closeModal={closeModal}
				requestDelete={requestDelete}
				isWorking={isWorking}
				setIsWorking={setIsWorking}
			/>

			<div className="code-snippets-preview-modal__priority">
				<span>{__('Priority', 'code-snippets')}</span>
				<span className="code-snippets-preview-modal__priority-value">{snippet.priority}</span>
			</div>

			{confirmDialog}
		</div>
	)
}

const PreviewTypeBadge: React.FC<{ type: SnippetType }> = ({ type }) =>
	<div className="code-snippets-preview-modal__badge">
		<Badge name={type} />
	</div>

const PreviewFooterActionsWrapper: React.FC<PropsWithChildren> = ({ children }) =>
	children
		? <div className="code-snippets-preview-modal__footer">
			<div className="code-snippets-preview-modal__buttons">{children}</div>
		</div>
		: null

/**
 * Modal for quickly viewing a snippet's code in a read-only CodeMirror editor,
 * without navigating to the edit page. Shared between local snippets and cloud
 * snippet previews. Passing a full snippet object adds a footer of snippet
 * actions, which requires the snippets API and snippets list contexts.
 */
export const SnippetPreviewModal: React.FC<SnippetPreviewModalProps> = ({
	title,
	code,
	type,
	isOpen,
	setIsOpen,
	snippet,
	footerActions
}) => {
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		if (!isOpen || !textareaRef.current || !window.wp.codeEditor) {
			return undefined
		}

		const instance = window.wp.codeEditor.initialize(
			textareaRef.current,
			{ codemirror: getPreviewEditorSettings(type) }
		)

		// CodeMirror hides the labeled source textarea and creates an unlabelled
		// internal input. The screenReaderLabel option only exists from CodeMirror
		// 5.59, while WordPress 5.5 ships 5.29, so label the input directly.
		instance.codemirror.getInputField().setAttribute('aria-label', CODE_PREVIEW_LABEL)

		return () => {
			(instance.codemirror as EditorFromTextArea).toTextArea()
		}
	}, [isOpen, type])

	return isOpen
		? <Modal
			className="code-snippets-preview-modal"
			onRequestClose={() => setIsOpen(false)}
			title={title}
			headerActions={<PreviewTypeBadge type={type} />}
		>
			<div className="code-snippets-preview-modal__editor">
				<textarea
					ref={textareaRef}
					readOnly
					aria-label={CODE_PREVIEW_LABEL}
					defaultValue={`${'php' === type ? '<?php\n\n' : ''}${code}`}
				/>
			</div>

			{snippet
				? <SnippetPreviewActions snippet={snippet} closeModal={() => setIsOpen(false)} />
				: <PreviewFooterActionsWrapper>{footerActions}</PreviewFooterActionsWrapper>}
		</Modal>
		: null
}
