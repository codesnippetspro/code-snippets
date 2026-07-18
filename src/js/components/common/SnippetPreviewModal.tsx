import React, { useEffect, useRef } from 'react'
import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { Badge } from './Badge'
import type { EditorFromTextArea } from 'codemirror'
import type { SnippetType } from '../../types/Snippet'

export interface SnippetPreviewModalProps {
	title: string
	code: string
	type: SnippetType
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
}

// Mirrors the type-to-mode mapping used by the live editor in SnippetTypeInput.
const EDITOR_MODES: Record<string, string> = {
	css: 'text/css',
	js: 'javascript',
	php: 'text/x-php',
	html: 'application/x-httpd-php'
}

/**
 * Modal for quickly viewing a snippet's code in a read-only CodeMirror editor,
 * without navigating to the edit page. Shared between local snippets and cloud
 * snippet previews.
 */
export const SnippetPreviewModal: React.FC<SnippetPreviewModalProps> = ({
	title,
	code,
	type,
	isOpen,
	setIsOpen
}) => {
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		if (!isOpen || !textareaRef.current || !window.wp.codeEditor) {
			return undefined
		}

		const instance = window.wp.codeEditor.initialize(textareaRef.current, {
			codemirror: {
				readOnly: true,
				lineNumbers: true,
				theme: window.CODE_SNIPPETS_MANAGE?.editorTheme ?? 'default',
				mode: EDITOR_MODES[type] ?? EDITOR_MODES.php
			}
		})

		// CodeMirror hides the labelled source textarea and creates an unlabelled
		// internal input. The screenReaderLabel option only exists from CodeMirror
		// 5.59, while WordPress 5.5 ships 5.29, so label the input directly.
		instance.codemirror.getInputField().setAttribute(
			'aria-label',
			__('Snippet code preview', 'code-snippets')
		)

		return () => {
			(instance.codemirror as EditorFromTextArea).toTextArea()
		}
	}, [isOpen, type])

	return isOpen
		? <Modal
			className="code-snippets-preview-modal"
			onRequestClose={() => setIsOpen(false)}
			title={title}
		>
			{/* The minimum-supported WordPress Modal (5.5–6.3) has no headerActions
			  * prop, so the badge renders in the content and CSS moves it into the
			  * header area. */}
			<div className="code-snippets-preview-modal__badge">
				<Badge name={type} />
			</div>
			<textarea
				ref={textareaRef}
				readOnly
				aria-label={__('Snippet code preview', 'code-snippets')}
				defaultValue={`${'php' === type ? '<?php\n\n' : ''}${code}`}
			/>
		</Modal>
		: null
}
