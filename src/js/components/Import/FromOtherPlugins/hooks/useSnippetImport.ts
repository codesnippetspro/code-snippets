import { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useImportersAPI, type ImportableSnippet } from '../../../../hooks/useImportersAPI'
import { isNetworkAdmin } from '../../../../utils/screen'

export const useSnippetImport = () => {
	const [snippets, setSnippets] = useState<ImportableSnippet[]>([])
	const [isLoadingSnippets, setIsLoadingSnippets] = useState(false)
	const [snippetsError, setSnippetsError] = useState<string | null>(null)
	const [isImporting, setIsImporting] = useState(false)
	const [importError, setImportError] = useState<string | null>(null)
	const [importSuccess, setImportSuccess] = useState<number[]>([])
	
	const importersAPI = useImportersAPI()

	const loadSnippets = async (importerName: string): Promise<boolean> => {
		if (!importerName) {
			alert(__('Please select an importer.', 'code-snippets'))
			return false
		}

		setIsLoadingSnippets(true)
		setSnippetsError(null)
		setSnippets([])
		clearResults()

		try {
			const response = await importersAPI.fetchSnippets(importerName)
			setSnippets(response.data)
			return true
		} catch (err) {
			setSnippetsError(err instanceof Error ? err.message : 'Unknown error')
			return false
		} finally {
			setIsLoadingSnippets(false)
		}
	}

	const importSnippets = async (
		importerName: string,
		selectedSnippetIds: number[],
		autoAddTags: boolean,
		tagValue: string
	): Promise<boolean> => {
		if (selectedSnippetIds.length === 0) {
			alert(__('Please select snippets to import.', 'code-snippets'))
			return false
		}

		if (!importerName) {
			alert(__('Please select an importer.', 'code-snippets'))
			return false
		}

		setIsImporting(true)
		setImportError(null)
		setImportSuccess([])

		try {
			const response = await importersAPI.importSnippets(importerName, {
				ids: selectedSnippetIds,
				network: isNetworkAdmin(),
				auto_add_tags: autoAddTags,
				tag_value: autoAddTags ? tagValue : undefined
			})

			setImportSuccess(response.data.imported)
			
			if (response.data.imported.length > 0) {
				setSnippets([])
				return true
			} else {
				alert(__('No snippets were imported.', 'code-snippets'))
				return false
			}
		} catch (err) {
			setImportError(err instanceof Error ? err.message : 'Unknown error')
			return false
		} finally {
			setIsImporting(false)
		}
	}

	const clearResults = () => {
		setImportSuccess([])
		setImportError(null)
	}

	const resetAll = () => {
		setSnippets([])
		clearResults()
		setSnippetsError(null)
	}

	return {
		snippets,
		isLoadingSnippets,
		snippetsError,
		isImporting,
		importError,
		importSuccess,
		loadSnippets,
		importSnippets,
		clearResults,
		resetAll
	}
}
