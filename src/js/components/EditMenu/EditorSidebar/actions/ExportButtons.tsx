import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetsAPI } from '../../../../hooks/useSnippetsAPI'
import { getSnippetType } from '../../../../utils/snippets/snippets'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'
import { downloadSnippetExportFile } from '../../../../utils/files'
import { Button } from '../../../common/Button'
import type { SnippetsExport } from '../../../../types/schema/SnippetsExport'
import type { Snippet } from '../../../../types/Snippet'

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
	const api = useSnippetsAPI()
	const { snippet } = useSnippetForm()

	return (
		<div className="snippet-export-buttons">
			<ExportButton
				name="export_snippet"
				label={__('Export', 'code-snippets')}
				makeRequest={api.export}
			/>

			{window.CODE_SNIPPETS_EDIT?.enableDownloads && 'cond' !== getSnippetType(snippet) && (
				<ExportButton
					name="export_snippet_code"
					label={__('Download Code', 'code-snippets')}
					makeRequest={api.exportCode}
				/>)}
		</div>
	)
}
