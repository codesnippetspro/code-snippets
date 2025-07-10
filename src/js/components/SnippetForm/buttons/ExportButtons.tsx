import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { useSnippetsAPI } from '../../../hooks/useSnippets'
import { downloadSnippetExportFile } from '../../../utils/files'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import type { SnippetsExport } from '../../../types/SnippetsExport'
import type { AxiosResponse } from 'axios'

export const ExportButtons: React.FC = () => {
	const api = useSnippetsAPI()
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	const handleFileResponse = (response: AxiosResponse<string | SnippetsExport>) => {
		const data = response.data
		setIsWorking(false)
		console.info('file response', response)

		if ('string' === typeof data) {
			downloadSnippetExportFile(data, snippet)
		} else {
			const JSON_INDENT_SPACES = 2
			downloadSnippetExportFile(JSON.stringify(data, undefined, JSON_INDENT_SPACES), snippet, 'json')
		}
	}

	return (
		<>
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
		</>
	)
}
