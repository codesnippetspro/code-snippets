import React from 'react'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { SnippetPreviewModal } from '../SnippetPreviewModal'
import { CloudSnippetDownloadButton } from './CloudSnippetDownloadButton'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

export interface CloudSnippetPreviewModalProps {
	isOpen: boolean
	snippet: CloudSnippetSchema
	setIsOpen: (isOpen: boolean) => void
}

export const CloudSnippetPreviewModal: React.FC<CloudSnippetPreviewModalProps> = ({ snippet, isOpen, setIsOpen }) =>
	<SnippetPreviewModal
		title={snippet.name}
		code={snippet.code}
		type={getSnippetType(snippet)}
		isOpen={isOpen}
		setIsOpen={setIsOpen}
		footerActions={<CloudSnippetDownloadButton snippet={snippet} />}
	/>
