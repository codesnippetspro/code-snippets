import React, { useEffect } from 'react'
import { Modal } from '@wordpress/components'
import { Prism } from '../../utils/Prism'

export interface SnippetPreviewModalProps {
	title: string
	code: string
	type: string
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
}

/**
 * Modal for quickly viewing a snippet's code with syntax highlighting,
 * without navigating to the edit page. Shared between local snippets and
 * cloud snippet previews.
 */
export const SnippetPreviewModal: React.FC<SnippetPreviewModalProps> = ({ title, code, type, isOpen, setIsOpen }) => {
	useEffect(() => {
		if (isOpen) {
			Prism.highlightAll()
		}
	}, [isOpen])

	return isOpen
		? <Modal onRequestClose={() => setIsOpen(false)} title={title}>
			<pre className="line-numbers">
				<code className={`language-${type}`}>
					{'php' === type ? '<?php\n\n' : ''}
					{code}
				</code>
			</pre>
		</Modal>
		: null
}
