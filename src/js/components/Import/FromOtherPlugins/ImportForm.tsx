import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import {
	ImporterSelector,
	ImportOptions,
	SimpleSnippetTable,
	StatusDisplay
} from './components'
import { ImportCard } from '../shared'
import {
	useImporterSelection,
	useSnippetImport,
	useImportSnippetSelection
} from './hooks'

export const ImportForm: React.FC = () => {
	const [autoAddTags, setAutoAddTags] = useState<boolean>(false)
	
	const importerSelection = useImporterSelection()
	const snippetImport = useSnippetImport()
	const snippetSelection = useImportSnippetSelection(snippetImport.snippets)

	const handleImporterChange = async (newImporter: string) => {
		importerSelection.handleImporterChange(newImporter)
		snippetSelection.clearSelection()
		snippetImport.resetAll()
		
		if (newImporter) {
			await snippetImport.loadSnippets(newImporter)
		}
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
				<p>{__('Loading importers...', 'code-snippets')}</p>
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
			<div className="import-form-container" style={{ maxWidth: '800px' }}>
				<p>{__('If you are using another Snippets plugin, you can import all existing snippets to your Code Snippets library.', 'code-snippets')}</p>
				
				<ImporterSelector
					importers={importerSelection.importers}
					selectedImporter={importerSelection.selectedImporter}
					onImporterChange={handleImporterChange}
					isLoading={snippetImport.isLoadingSnippets}
				/>

				{snippetImport.snippetsError && (
					<StatusDisplay
						type="error"
						title={__('Error loading snippets', 'code-snippets')}
						message={snippetImport.snippetsError}
					/>
				)}

				{snippetImport.importError && (
					<StatusDisplay
						type="error"
						title={__('Error importing snippets', 'code-snippets')}
						message={snippetImport.importError}
					/>
				)}

				{snippetImport.importSuccess.length > 0 && (
					<StatusDisplay
						type="success"
						title={`${snippetImport.importSuccess.length} ${__('Snippets imported!', 'code-snippets')}`}
						message={__('We successfully imported all snippets to your library. Go to ', 'code-snippets')}
						showSnippetsLink
					/>
				)}

				{importerSelection.selectedImporter && 
				 !snippetImport.isLoadingSnippets && 
				 !snippetImport.snippetsError && 
				 snippetImport.snippets.length === 0 &&
				 snippetImport.importSuccess.length === 0 && (
					<ImportCard>
						<div style={{ textAlign: 'center', padding: '40px 20px', color: '#666' }}>
							<div style={{ fontSize: '48px', marginBottom: '16px' }}>📭</div>
							<h3 style={{ margin: '0 0 8px 0', fontSize: '18px', color: '#333' }}>
								{__('No snippets found', 'code-snippets')}
							</h3>
							<p style={{ margin: '0', fontSize: '14px' }}>
								{__('No snippets were found for the selected plugin. Make sure the plugin is installed and has snippets configured.', 'code-snippets')}
							</p>
						</div>
					</ImportCard>
				)}

				{snippetImport.snippets.length > 0 && (
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
							onImport={handleImport}
							isImporting={snippetImport.isImporting}
						/>
					</>
				)}
			</div>
		</div>
	)
}
