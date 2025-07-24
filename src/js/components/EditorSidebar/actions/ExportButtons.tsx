import React from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { downloadSnippetExportFile } from '../../../utils/files'
import { Button } from '../../common/Button'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const ExportButtons: React.FC = () => {
	const { snippetsAPI } = useRestAPI()
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	return (
		<div className="snippet-export-buttons">
			<Button
				name="export_snippet"
				onClick={() => {
					setIsWorking(true)

					snippetsAPI.export(snippet)
						.then(response => downloadSnippetExportFile(response, snippet))
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
						snippetsAPI.exportCode(snippet)
							.then(response => downloadSnippetExportFile(response, snippet))
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
