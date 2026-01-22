import React, { ReactNode, useEffect, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { ImportCard } from '../common/ImportCard'
import { ImporterSelector } from './components/ImporterSelector'
import { ImportOptions } from './components/ImportOptions'
import { SimpleSnippetTable } from './components/SimpleSnippetTable'
import { useImporterSelection } from './hooks/useImporterSelection'
import { useImportSnippetSelection } from './hooks/useImportSnippetSelection'
import { useSnippetImport } from './hooks/useSnippetImport'
import { createInterpolateElement } from '@wordpress/element'

interface StatusDisplayProps {
	type: 'error' | 'success'
	title: string
	message: ReactNode
}

const StatusDisplay: React.FC<StatusDisplayProps> = ({ type, title, message }) =>
	<ImportCard variant="controls" className="import-section-status">
		{'error' === type
			? <div className="error"><span>✕</span></div>
			: <div className="success"><span>✓</span></div>}

		<div>
			<h3>{title}</h3>
			<p>{message}</p>
		</div>
	</ImportCard>

export const MigrateForm: React.FC = () => {
	const [autoAddTags, setAutoAddTags] = useState(false)

	const importerSelection = useImporterSelection()
	const snippetImport = useSnippetImport()
	const snippetSelection = useImportSnippetSelection(snippetImport.snippets)

	useEffect(() => {
		if (importerSelection.selectedImporter) {
			void snippetImport.loadSnippets(importerSelection.selectedImporter)
		}
	}, [importerSelection.selectedImporter])

	const handleImporterChange = async (newImporter: string) => {
		importerSelection.handleImporterChange(newImporter)
		snippetSelection.clearSelection()
		snippetImport.resetAll()
	}

	const handleImport = async () => {
		const selectedIds = Array.from(snippetSelection.selectedSnippets)
		const success = await snippetImport.importSnippets(
			importerSelection.selectedImporter,
			selectedIds,
			autoAddTags,
			importerSelection.tagValue
		)

		if (success) {
			snippetSelection.clearSelection()
		}
	}

	if (importerSelection.isLoading) {
		return (
			<div className="wrap">
				<p>{__('Loading importers…', 'code-snippets')}</p>
			</div>
		)
	}

	if (importerSelection.error) {
		return (
			<div className="wrap">
				<div className="notice notice-error">
					<p>{__('Error loading importers:', 'code-snippets')} {importerSelection.error}</p>
				</div>
			</div>
		)
	}

	return (
		<div className="wrap">
			<div className="migrate-form-container">
				<p>{__('If you are using another snippets plugin, you can import those existing snippets to your Code Snippets library.', 'code-snippets')}</p>

				<ImporterSelector
					importers={importerSelection.importers}
					selectedImporter={importerSelection.selectedImporter}
					onImporterChange={newImporter => void handleImporterChange(newImporter)}
					isLoading={snippetImport.isLoadingSnippets}
				/>

				{snippetImport.snippetsError && (
					<StatusDisplay
						type="error"
						title={__('Error loading snippets', 'code-snippets')}
						message={snippetImport.snippetsError}
					/>)}

				{snippetImport.importError && (
					<StatusDisplay
						type="error"
						title={__('Error importing snippets', 'code-snippets')}
						message={snippetImport.importError}
					/>)}

				{0 < snippetImport.importSuccess.length && (
					<StatusDisplay
						type="success"
						title={sprintf(
							// translators: %d: nimber of imported snippets.
							__('%d Snippets imported!', 'code-snippets'),
							snippetImport.importSuccess.length
						)}
						message={
							createInterpolateElement(
								__('We successfully imported all snippets to your library. Go to <a>Code Snippets Library</a>.', 'code-snippets'),
								{ a: <a href={window.CODE_SNIPPETS?.urls?.manage} /> }
							)}
					/>)}

				{importerSelection.selectedImporter &&
					!snippetImport.isLoadingSnippets &&
					!snippetImport.snippetsError &&
					0 === snippetImport.snippets.length &&
					0 === snippetImport.importSuccess.length && (
						<ImportCard className="no-snippets-card">
							<div className="card-inner">
								<div className="card-icon">📭</div>
								<h3>{__('No snippets found', 'code-snippets')}</h3>
								<p>{__('No snippets were found for the selected plugin. Make sure the plugin is installed and has snippets configured.', 'code-snippets')}</p>
							</div>
						</ImportCard>)}

				{0 < snippetImport.snippets.length && (
					<>
						<ImportOptions
							autoAddTags={autoAddTags}
							tagValue={importerSelection.tagValue}
							onAutoAddTagsChange={setAutoAddTags}
							onTagValueChange={importerSelection.setTagValue}
						/>

						<SimpleSnippetTable
							snippets={snippetImport.snippets}
							selectedSnippets={snippetSelection.selectedSnippets}
							onSnippetToggle={snippetSelection.handleSnippetToggle}
							onSelectAll={snippetSelection.handleSelectAll}
							onImport={() => void handleImport()}
							isImporting={snippetImport.isImporting}
						/>
					</>
				)}
			</div>
		</div>
	)
}
