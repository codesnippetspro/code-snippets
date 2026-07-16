import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useEffect, useRef } from 'react'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { handleUnknownError } from '../../utils/errors'
import { downloadSnippetExportFile } from '../../utils/files'
import { canModifySnippet, cloneSnippetObject, getSnippetEditUrl } from '../../utils/snippets/snippets'
import { Badge } from './Badge'
import { Button } from './Button'
import { useDeleteSnippet } from './DeleteButton'
import type { BadgeName } from './Badge'
import type { ComponentProps, ReactNode } from 'react'
import type { EditorFromTextArea } from 'codemirror'
import type { Snippet } from '../../types/Snippet'

// The vendor type only declares `title` as a string, but the component renders
// it as a plain ReactNode, which is needed here to include the type badge.
const ModalWithNodeTitle = Modal as React.FC<Omit<ComponentProps<typeof Modal>, 'title'> & { title?: ReactNode }>

export interface SnippetPreviewModalProps {
	title: string
	code: string
	type: string
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
	snippet?: Snippet
}

// Mirrors the type-to-mode mapping used by the live editor in SnippetTypeInput.
const EDITOR_MODES: Record<string, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

interface SnippetPreviewActionsProps {
	snippet: Snippet
	closeModal: () => void
}

interface SnippetPreviewButtonsProps extends SnippetPreviewActionsProps {
	requestDelete: () => void
}

const SnippetPreviewButtons: React.FC<SnippetPreviewButtonsProps> = ({ snippet, closeModal, requestDelete }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const canModify = canModifySnippet(snippet)

	return (
		<div className="code-snippets-preview-modal__buttons">
			<a className="button button-primary" href={getSnippetEditUrl(snippet)}>
				{snippet.locked || !canModify ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
			</a>

			{canModify
				? <Button
					secondary
					onClick={() => {
						api.create(cloneSnippetObject(snippet))
							.then(refreshSnippetsList)
							.then(closeModal)
							.catch(handleUnknownError)
					}}
				>
					{__('Clone', 'code-snippets')}
				</Button>
				: null}

			<Button
				secondary
				onClick={() => {
					api.export(snippet)
						.then(response => downloadSnippetExportFile(response, snippet))
						.catch(handleUnknownError)
				}}
			>
				{__('Export', 'code-snippets')}
			</Button>

			{snippet.locked || !canModify
				? null
				: <Button
					link
					className="code-snippets-preview-modal__trash"
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
const SnippetPreviewActions: React.FC<SnippetPreviewActionsProps> = ({ snippet, closeModal }) => {
	const { refreshSnippetsList } = useSnippetsList()
	const { requestDelete, confirmDialog } = useDeleteSnippet({
		snippet,
		onSuccess: () => {
			closeModal()
			return refreshSnippetsList()
		},
		onError: handleUnknownError
	})

	return (
		<div className="code-snippets-preview-modal__footer">
			<SnippetPreviewButtons snippet={snippet} closeModal={closeModal} requestDelete={requestDelete} />

			<div className="code-snippets-preview-modal__priority">
				<span>{__('Priority', 'code-snippets')}</span>
				<span className="code-snippets-preview-modal__priority-value">{snippet.priority}</span>
			</div>

			{confirmDialog}
		</div>
	)
}

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
	snippet
}) => {
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		if (!isOpen || !textareaRef.current || !window.wp.codeEditor) {
			return undefined
		}

		const instance = window.wp.codeEditor.initialize(textareaRef.current, {
			codemirror: {
				readOnly: 'nocursor',
				lineNumbers: true,
				theme: window.CODE_SNIPPETS_MANAGE?.editorTheme ?? 'default',
				mode: EDITOR_MODES[type] ?? EDITOR_MODES.php
			}
		})

		return () => {
			(instance.codemirror as EditorFromTextArea).toTextArea()
		}
	}, [isOpen, type])

	return isOpen
		? <ModalWithNodeTitle
			className="code-snippets-preview-modal"
			onRequestClose={() => setIsOpen(false)}
			title={title}
			headerActions={<Badge name={type as BadgeName} />}
		>
			<div className="code-snippets-preview-modal__editor">
				<textarea
					ref={textareaRef}
					readOnly
					aria-label={__('Snippet code preview', 'code-snippets')}
					defaultValue={`${'php' === type ? '<?php\n\n' : ''}${code}`}
				/>
			</div>

			{snippet
				? <SnippetPreviewActions snippet={snippet} closeModal={() => setIsOpen(false)} />
				: null}
		</ModalWithNodeTitle>
		: null
}
