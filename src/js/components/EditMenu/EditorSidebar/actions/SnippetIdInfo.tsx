import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { CopyToClipboardButton } from '../../../common/CopyToClipboardButton'

export const SnippetIdInfo: React.FC = () => {
	const { snippet } = useSnippetForm()

	return snippet.id
		? <div className="inline-form-field">
			<strong>{__('ID', 'code-snippets')}</strong>
			<span className="snippet-id-value">{snippet.id}</span>
			<CopyToClipboardButton small text={String(snippet.id)} />
		</div>
		: null
}
