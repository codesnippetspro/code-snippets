import React, { useEffect, useRef } from 'react'
import { Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { Badge } from './Badge'
import type { BadgeName } from './Badge'
import type { EditorFromTextArea } from 'codemirror'

export interface SnippetPreviewModalProps {
	title: string
	code: string
	type: string
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
		? <Modal
			className="code-snippets-preview-modal"
			onRequestClose={() => setIsOpen(false)}
			title={title}
			headerActions={<Badge name={type as BadgeName} />}
		>
			<textarea
				ref={textareaRef}
				readOnly
				aria-label={__('Snippet code preview', 'code-snippets')}
				defaultValue={`${'php' === type ? '<?php\n\n' : ''}${code}`}
			/>
		</Modal>
		: null
}
