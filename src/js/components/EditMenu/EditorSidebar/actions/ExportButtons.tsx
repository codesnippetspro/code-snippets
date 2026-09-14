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
	icon: string
	title?: string
	makeRequest: (snippet: Snippet) => Promise<SnippetsExport | string>
}

const ExportButton: React.FC<ExportButtonProps> = ({ name, label, icon, title, makeRequest }) => {
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
		<Button name={name} onClick={handleClick} disabled={isWorking} title={title}>
			<span className={`dashicons ${icon}`} aria-hidden="true" />
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
				icon="dashicons-upload"
				title={__('Download snippet as JSON', 'code-snippets')}
				makeRequest={api.export}
			/>

			{window.CODE_SNIPPETS_EDIT?.enableDownloads && 'cond' !== getSnippetType(snippet) && (
				<ExportButton
					name="export_snippet_code"
					label={__('Download', 'code-snippets')}
					icon="dashicons-download"
					makeRequest={api.exportCode}
				/>)}
		</div>
	)
}
