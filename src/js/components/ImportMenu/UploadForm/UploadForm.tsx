import React, { useEffect, useRef, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { ImportCard } from '../common/ImportCard'
import { DragDropUploadArea } from './components/DragDropUploadArea'
import { DuplicateActionSelector } from './components/DuplicateActionSelector'
import { ImportResultDisplay } from './components/ImportResultDisplay'
import { SelectedFilesList } from './components/SelectedFilesList'
import { SnippetSelectionTable } from './components/SnippetSelectionTable'
import { useFileSelection } from './hooks/useFileSelection'
import { useImportWorkflow } from './hooks/useImportWorkflow'
import { useSnippetSelection } from './hooks/useSnippetSelection'
import { createInterpolateElement } from '@wordpress/element'

type DuplicateAction = 'ignore' | 'replace' | 'skip'
type Step = 'upload' | 'select'

interface UploadStepProps {
	importWorkflow: ReturnType<typeof useImportWorkflow>
	fileSelection: ReturnType<typeof useFileSelection>
	snippetSelection: ReturnType<typeof useSnippetSelection>
	duplicateAction: DuplicateAction
	setDuplicateAction: (action: DuplicateAction) => void
	onSuccess: VoidFunction
}

const UploadStep: React.FC<UploadStepProps> = ({
	onSuccess,
	importWorkflow,
	fileSelection,
	snippetSelection,
	duplicateAction,
	setDuplicateAction
}) => {
	const { isUploading, clearUploadResult } = importWorkflow

	const isUploadDisabled = !fileSelection.selectedFiles ||
		0 === fileSelection.selectedFiles.length ||
		isUploading

	const handleFileSelect = (files: FileList | null) => {
		fileSelection.handleFileSelect(files)
		clearUploadResult()
	}

	const handleParseFiles = async () => {
		if (fileSelection.selectedFiles) {
			const success = await importWorkflow.parseFiles(fileSelection.selectedFiles)

			if (success) {
				snippetSelection.clearSelection()
				onSuccess()
			}
		}
	}

	return importWorkflow.uploadResult?.success
		? null
		: <>
			<DuplicateActionSelector value={duplicateAction} onChange={setDuplicateAction} />

			<ImportCard className="import-upload-card">
				<h2>{__('Choose files', 'code-snippets')}</h2>
				<p className="description">
					{__('Choose one or more Code Snippets (.xml or .json) files to parse and preview.', 'code-snippets')}
				</p>

				<DragDropUploadArea
					fileInputRef={fileSelection.fileInputRef}
					onFileSelect={handleFileSelect}
					disabled={isUploading}
				/>

				{fileSelection.selectedFiles && 0 < fileSelection.selectedFiles.length && (
					<SelectedFilesList
						files={fileSelection.selectedFiles}
						onRemoveFile={fileSelection.removeFile}
					/>)}

				<footer>
					<Button
						primary
						onClick={() => void handleParseFiles()}
						disabled={isUploadDisabled}
					>
						{isUploading
							? __('Uploading files…', 'code-snippets')
							: __('Upload files', 'code-snippets')}
					</Button>
				</footer>
			</ImportCard>
		</>
}

interface SelectStepProps {
	onCancel: VoidFunction
	importWorkflow: ReturnType<typeof useImportWorkflow>
	snippetSelection: ReturnType<typeof useSnippetSelection>
	duplicateAction: DuplicateAction
	fileSelection: ReturnType<typeof useFileSelection>
}

const SelectStep: React.FC<SelectStepProps> = ({
	importWorkflow,
	snippetSelection,
	duplicateAction,
	fileSelection,
	onCancel
}) => {
	const { isImporting } = importWorkflow
	const selectSectionRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (selectSectionRef.current) {
			selectSectionRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' })
		}
	}, [selectSectionRef])

	const handleImportSelected = async () => {
		const snippetsToImport = snippetSelection.getSelectedSnippets()
		await importWorkflow.importSnippets(snippetsToImport, duplicateAction)
	}

	const handleBackToUpload = () => {
		fileSelection.clearFiles()
		snippetSelection.clearSelection()
		importWorkflow.resetWorkflow()
		onCancel()
	}

	const isImportDisabled = 0 === snippetSelection.selectedSnippets.size || isImporting

	return (
		<ImportCard ref={selectSectionRef} className="import-select-card snippets-table-card">
			<div className="return-link">
				<Button link onClick={handleBackToUpload}>
					{__('← Upload Different Files', 'code-snippets')}
				</Button>
			</div>
			<div className="tablenav top">
				<div>
					<h3>{sprintf(
						// translators: %d: number of available snippets.
						__('Available snippets (%d)', 'code-snippets'),
						importWorkflow.availableSnippets.length
					)}</h3>
					<p>
						{__('Select the snippets you would like to import.', 'code-snippets')}
					</p>
				</div>
				<div className="table-actions">
					<Button onClick={snippetSelection.handleSelectAll}>
						{snippetSelection.isAllSelected
							? __('Deselect All', 'code-snippets')
							: __('Select All', 'code-snippets')}
					</Button>
					<Button
						primary
						onClick={() => void handleImportSelected()}
						disabled={isImportDisabled}
					>
						{isImporting
							? __('Importing…', 'code-snippets')
							// translators: %d: number of selected snippets.
							: sprintf(__('Import Selected (%d)', 'code-snippets'), snippetSelection.selectedSnippets.size)}
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

			<div className="tablenav bottom">
				<Button onClick={snippetSelection.handleSelectAll}>
					{snippetSelection.isAllSelected
						? __('Deselect All', 'code-snippets')
						: __('Select All', 'code-snippets')
					}
				</Button>

				<Button
					primary
					onClick={() => void handleImportSelected()}
					disabled={isImportDisabled}
				>
					{importWorkflow.isImporting
						? __('Importing…', 'code-snippets')
						: __('Import Selected', 'code-snippets')} ({snippetSelection.selectedSnippets.size})
				</Button>
			</div>
		</ImportCard>
	)
}

export const UploadForm: React.FC = () => {
	const [duplicateAction, setDuplicateAction] = useState<DuplicateAction>('ignore')
	const [currentStep, setCurrentStep] = useState<Step>('upload')

	const fileSelection = useFileSelection()
	const importWorkflow = useImportWorkflow()
	const snippetSelection = useSnippetSelection(importWorkflow.availableSnippets)

	return (
		<div className="wrap">
			<div className="upload-form-container">
				<p>{__('Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets')}</p>

				<p>
					{createInterpolateElement(
						__('Afterward, you will need to visit the <a>All Snippets</a> page to activate the imported snippets.', 'code-snippets'),
						{ a: <a href={window.CODE_SNIPPETS?.urls.manage} /> }
					)}
				</p>

				{'upload' === currentStep && (
					<UploadStep
						{...{ importWorkflow, fileSelection, snippetSelection, duplicateAction, setDuplicateAction }}
						onSuccess={() => setCurrentStep('select')}
					/>)}

				{'select' === currentStep && 0 < importWorkflow.availableSnippets.length && !importWorkflow.uploadResult?.success && (
					<SelectStep
						{...{ importWorkflow, fileSelection, snippetSelection, duplicateAction, setDuplicateAction }}
						onCancel={() => setCurrentStep('upload')}
					/>)}

				{importWorkflow.uploadResult && <ImportResultDisplay {...importWorkflow.uploadResult} />}
			</div>
		</div>
	)
}
