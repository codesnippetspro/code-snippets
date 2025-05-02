import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { downloadSnippetExportFile } from '../../../utils/files'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import type { SnippetsExport } from '../../../types/schema/SnippetsExport'

export const ExportButtons: React.FC = () => {
	const api = useSnippetsAPI()
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	const handleFileResponse = (response: string | SnippetsExport) => {
		setIsWorking(false)
		console.info('file response', response)

		if ('string' === typeof response) {
			downloadSnippetExportFile(response, snippet)
		} else {
			const JSON_INDENT_SPACES = 2
			downloadSnippetExportFile(JSON.stringify(response, undefined, JSON_INDENT_SPACES), snippet, 'json')
		}
	}

	return (
		<div className="snippet-export-buttons">
			<Button
				name="export_snippet"
				onClick={() => {
					setIsWorking(true)

					api.export(snippet)
						.then(handleFileResponse)
						// translators: %s: error message.
						.catch((error: unknown) => handleRequestError(error, __('Could not download export file.', 'code-snippets')))
				}}
				disabled={isWorking}
			>
				{__('Export', 'code-snippets')}
			</Button>

			{window.CODE_SNIPPETS_EDIT?.enableDownloads
				? <Button
					name="export_snippet_code"
					onClick={() => {
						api.exportCode(snippet)
							.then(handleFileResponse)
							// translators: %s: error message.
							.catch((error: unknown) => handleRequestError(error, __('Could not download file.', 'code-snippets')))
					}}
					disabled={isWorking}
				>
					{__('Export Code', 'code-snippets')}
				</Button>
				: ''}
		</div>
	)
}
