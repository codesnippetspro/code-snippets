import React, { useState, useRef, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import {
	DuplicateActionSelector,
	DragDropUploadArea,
	SelectedFilesList,
	SnippetSelectionTable,
	ImportResultDisplay
} from './components'
import { ImportCard } from '../shared'
import {
	useFileSelection,
	useSnippetSelection,
	useImportWorkflow
} from './hooks'

type DuplicateAction = 'ignore' | 'replace' | 'skip'
type Step = 'upload' | 'select'

export const FileUploadForm: React.FC = () => {
	const [duplicateAction, setDuplicateAction] = useState<DuplicateAction>('ignore')
	const [currentStep, setCurrentStep] = useState<Step>('upload')
	const selectSectionRef = useRef<HTMLDivElement>(null)

	const fileSelection = useFileSelection()
	const importWorkflow = useImportWorkflow()
	const snippetSelection = useSnippetSelection(importWorkflow.availableSnippets)

	useEffect(() => {
		if (currentStep === 'select' && selectSectionRef.current) {
			selectSectionRef.current.scrollIntoView({ 
				behavior: 'smooth', 
				block: 'start' 
			})
		}
	}, [currentStep])

	const handleFileSelect = (files: FileList | null) => {
		fileSelection.handleFileSelect(files)
		importWorkflow.clearUploadResult()
	}

	const handleParseFiles = async () => {
		if (!fileSelection.selectedFiles) return

		const success = await importWorkflow.parseFiles(fileSelection.selectedFiles)
		if (success) {
			snippetSelection.clearSelection()
			setCurrentStep('select')
		}
	}

	const handleImportSelected = async () => {
		const snippetsToImport = snippetSelection.getSelectedSnippets()
		await importWorkflow.importSnippets(snippetsToImport, duplicateAction)
	}

	const handleBackToUpload = () => {
		setCurrentStep('upload')
		fileSelection.clearFiles()
		snippetSelection.clearSelection()
		importWorkflow.resetWorkflow()
	}

	const isUploadDisabled = !fileSelection.selectedFiles || 
							 fileSelection.selectedFiles.length === 0 || 
							 importWorkflow.isUploading

	const isImportDisabled = snippetSelection.selectedSnippets.size === 0 || 
							 importWorkflow.isImporting

	return (
		<div className="wrap">
			<div className="import-form-container" style={{ maxWidth: '800px' }}>
				<p>{__('Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets')}</p>
				
				<p>
					{__('Afterward, you will need to visit the ', 'code-snippets')}
					<a href="admin.php?page=snippets">
						{__('All Snippets', 'code-snippets')}
					</a>
					{__(' page to activate the imported snippets.', 'code-snippets')}
				</p>

				{currentStep === 'upload' && (
					<>

						{(!importWorkflow.uploadResult || !importWorkflow.uploadResult.success) && (
							<>
								<DuplicateActionSelector
									value={duplicateAction}
									onChange={setDuplicateAction}
								/>

								<ImportCard>
									<h2 style={{ margin: '0 0 1em 0' }}>{__('Choose Files', 'code-snippets')}</h2>
									<p className="description" style={{ marginBottom: '1em' }}>
										{__('Choose one or more Code Snippets (.xml or .json) files to parse and preview.', 'code-snippets')}
									</p>

									<DragDropUploadArea
										fileInputRef={fileSelection.fileInputRef}
										onFileSelect={handleFileSelect}
										disabled={importWorkflow.isUploading}
									/>

									{fileSelection.selectedFiles && fileSelection.selectedFiles.length > 0 && (
										<SelectedFilesList
											files={fileSelection.selectedFiles}
											onRemoveFile={fileSelection.removeFile}
										/>
									)}

									<div style={{ textAlign: 'center' }}>
										<Button
											primary
											onClick={handleParseFiles}
											disabled={isUploadDisabled}
											style={{ minWidth: '200px' }}
										>
											{importWorkflow.isUploading 
												? __('Uploading files...', 'code-snippets')
												: __('Upload files', 'code-snippets')
											}
										</Button>
									</div>
								</ImportCard>
							</>
						)}
					</>
				)}

				{currentStep === 'select' && importWorkflow.availableSnippets.length > 0 && !importWorkflow.uploadResult?.success && (
					<ImportCard ref={selectSectionRef}>
						<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '20px' }}>
							<Button onClick={handleBackToUpload} className="button-link">
								{__('← Upload Different Files', 'code-snippets')}
							</Button>
						</div>
						<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
							<div>
								<h3 style={{ margin: '0' }}>{__('Available Snippets', 'code-snippets')} ({importWorkflow.availableSnippets.length})</h3>
								<p style={{ margin: '0.5em 0 1em 0', color: '#666' }}>
									{__('Select the snippets you want to import:', 'code-snippets')}
								</p>
							</div>
							<div>
								<Button onClick={snippetSelection.handleSelectAll} style={{ marginRight: '10px' }}>
									{snippetSelection.isAllSelected 
										? __('Deselect All', 'code-snippets')
										: __('Select All', 'code-snippets')
									}
								</Button>
								<Button 
									primary
									onClick={handleImportSelected}
									disabled={isImportDisabled}
								>
									{importWorkflow.isImporting 
										? __('Importing...', 'code-snippets')
										: __('Import Selected', 'code-snippets')} ({snippetSelection.selectedSnippets.size})
								</Button>
							</div>
						</div>

						<SnippetSelectionTable
							snippets={importWorkflow.availableSnippets}
							selectedSnippets={snippetSelection.selectedSnippets}
							isAllSelected={snippetSelection.isAllSelected}
							onSnippetToggle={snippetSelection.handleSnippetToggle}
							onSelectAll={snippetSelection.handleSelectAll}
						/>
						
						<div style={{ textAlign: 'end', marginTop: '1em' }}>
							<Button onClick={snippetSelection.handleSelectAll} style={{ marginRight: '10px' }}>
								{snippetSelection.isAllSelected 
									? __('Deselect All', 'code-snippets')
									: __('Select All', 'code-snippets')
								}
							</Button>
							<Button 
								primary
								onClick={handleImportSelected}
								disabled={isImportDisabled}
							>
								{importWorkflow.isImporting 
									? __('Importing...', 'code-snippets')
									: __('Import Selected', 'code-snippets')} ({snippetSelection.selectedSnippets.size})
							</Button>
						</div>
					</ImportCard>
				)}

				{importWorkflow.uploadResult && (
					<ImportResultDisplay result={importWorkflow.uploadResult} />
				)}
			</div>
		</div>
	)
}
