import React from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { Button } from '../../common/Button'
import { downloadSnippetExportFile } from '../../../utils/files'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import type { Snippet } from '../../../types/Snippet'
import type { SnippetsExport } from '../../../types/schema/SnippetsExport'

interface ExportButtonProps {
	name: string
	label: string
	makeRequest: (snippet: Snippet) => Promise<SnippetsExport | string>
}

const ExportButton: React.FC<ExportButtonProps> = ({ name, label, makeRequest }) => {
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	const handleClick = () => {
		setIsWorking(true)

		makeRequest(snippet)
			.then(response => downloadSnippetExportFile(response, snippet))
			// translators: %s: error message.
			.catch((error: unknown) => handleRequestError(error, __('Could not download export file.', 'code-snippets')))
			.finally(() => setIsWorking(false))
	}

	return (
		<Button name={name} onClick={handleClick} disabled={isWorking}>
			{label}
		</Button>
	)
}

export const ExportButtons: React.FC = () => {
	const { snippetsAPI } = useRestAPI()

	return (
		<div className="snippet-export-buttons">
			<ExportButton
				name="export_snippet"
				label={__('Export', 'code-snippets')}
				makeRequest={snippetsAPI.export}
			/>

			{window.CODE_SNIPPETS_EDIT?.enableDownloads
				? <ExportButton
					name="export_snippet_code"
					label={__('Export Code', 'code-snippets')}
					makeRequest={snippetsAPI.exportCode}
				/>
				: null}
		</div>
	)
}
