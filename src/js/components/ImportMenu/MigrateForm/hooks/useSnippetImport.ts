import { useCallback, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { REST_NAMESPACED } from '../../../../utils/restAPI'
import { isNetworkAdmin } from '../../../../utils/screen'

export interface Importer {
	name: string
	title: string
	is_active: boolean
}

export interface ImportableSnippet {
	id: number
	title: string
	table_data: {
		id: number
		title: string
	}
}

export interface ImportRequest {
	ids: number[]
	network?: boolean
	auto_add_tags?: boolean
	tag_value?: string
}

export interface ImportResponse {
	imported: number[]
}

export const useSnippetImport = () => {
	const [snippets, setSnippets] = useState<ImportableSnippet[]>([])
	const [isLoadingSnippets, setIsLoadingSnippets] = useState(false)
	const [snippetsError, setSnippetsError] = useState<string | null>(null)
	const [isImporting, setIsImporting] = useState(false)
	const [importError, setImportError] = useState<string | null>(null)
	const [importSuccess, setImportSuccess] = useState<number[]>([])

	const { api } = useRestAPI()

	const loadSnippets = useCallback(async (importerName: string): Promise<boolean> => {
		if (!importerName) {
			alert(__('Please select an importer.', 'code-snippets'))
			return false
		}

		setIsLoadingSnippets(true)
		setSnippetsError(null)
		setSnippets([])
		clearResults()

		try {
			const response = await api.get<ImportableSnippet[]>(`${REST_NAMESPACED}1/${importerName}`)
			setSnippets(response)
			return true
		} catch (error) {
			setSnippetsError(error instanceof Error ? error.message : 'Unknown error')
			return false
		} finally {
			setIsLoadingSnippets(false)
		}
	}, [api])

	const importSnippets = async (
		importerName: string,
		selectedSnippetIds: number[],
		autoAddTags: boolean,
		tagValue: string
	): Promise<boolean> => {
		if (0 === selectedSnippetIds.length) {
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
			const request: ImportRequest = {
				ids: selectedSnippetIds,
				network: isNetworkAdmin(),
				auto_add_tags: autoAddTags,
				tag_value: autoAddTags ? tagValue : undefined
			}

			const response = await api.post<ImportResponse, ImportRequest>(`${REST_NAMESPACED}1/${importerName}/import`, request)

			setImportSuccess(response.imported)

			if (0 < response.imported.length) {
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
