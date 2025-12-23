import { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useFileUploadAPI, type ImportableSnippet } from '../../../../hooks/useFileUploadAPI'
import { isNetworkAdmin } from '../../../../utils/screen'

type DuplicateAction = 'ignore' | 'replace' | 'skip'

interface UploadResult {
	success: boolean
	message: string
	imported?: number
	warnings?: string[]
}

export const useImportWorkflow = () => {
	const [isUploading, setIsUploading] = useState(false)
	const [isImporting, setIsImporting] = useState(false)
	const [availableSnippets, setAvailableSnippets] = useState<ImportableSnippet[]>([])
	const [uploadResult, setUploadResult] = useState<UploadResult | null>(null)
	
	const fileUploadAPI = useFileUploadAPI()

	const parseFiles = async (files: FileList): Promise<boolean> => {
		if (!files || files.length === 0) {
			alert(__('Please select files to upload.', 'code-snippets'))
			return false
		}

		setIsUploading(true)
		setUploadResult(null)

		try {
			const response = await fileUploadAPI.parseFiles({ files })

			setAvailableSnippets(response.data.snippets)
			
			if (response.data.warnings && response.data.warnings.length > 0) {
				setUploadResult({
					success: true,
					message: response.data.message,
					warnings: response.data.warnings
				})
			}

			return true

		} catch (error) {
			console.error('Parse error:', error)
			setUploadResult({
				success: false,
				message: error instanceof Error ? error.message : __('An unknown error occurred.', 'code-snippets')
			})
			return false
		} finally {
			setIsUploading(false)
		}
	}

	const importSnippets = async (
		snippetsToImport: ImportableSnippet[], 
		duplicateAction: DuplicateAction
	): Promise<boolean> => {
		if (snippetsToImport.length === 0) {
			alert(__('Please select snippets to import.', 'code-snippets'))
			return false
		}

		setIsImporting(true)
		setUploadResult(null)

		try {
			const response = await fileUploadAPI.importSnippets({
				snippets: snippetsToImport,
				duplicate_action: duplicateAction,
				network: isNetworkAdmin()
			})

			setUploadResult({
				success: true,
				message: response.data.message,
				imported: response.data.imported
			})

			return true

		} catch (error) {
			console.error('Import error:', error)
			setUploadResult({
				success: false,
				message: error instanceof Error ? error.message : __('An unknown error occurred.', 'code-snippets')
			})
			return false
		} finally {
			setIsImporting(false)
		}
	}

	const resetWorkflow = () => {
		setAvailableSnippets([])
		setUploadResult(null)
	}

	const clearUploadResult = () => {
		setUploadResult(null)
	}

	return {
		isUploading,
		isImporting,
		availableSnippets,
		uploadResult,
		parseFiles,
		importSnippets,
		resetWorkflow,
		clearUploadResult
	}
}
