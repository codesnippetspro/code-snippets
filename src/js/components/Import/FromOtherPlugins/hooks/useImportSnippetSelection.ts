import { useState } from 'react'
import type { ImportableSnippet } from '../../../../hooks/useImportersAPI'

export const useImportSnippetSelection = (availableSnippets: ImportableSnippet[]) => {
	const [selectedSnippets, setSelectedSnippets] = useState<Set<number>>(new Set())

	const handleSnippetToggle = (snippetId: number) => {
		const newSelected = new Set(selectedSnippets)
		if (newSelected.has(snippetId)) {
			newSelected.delete(snippetId)
		} else {
			newSelected.add(snippetId)
		}
		setSelectedSnippets(newSelected)
	}

	const handleSelectAll = () => {
		if (selectedSnippets.size === availableSnippets.length) {
			setSelectedSnippets(new Set())
		} else {
			setSelectedSnippets(new Set(availableSnippets.map(snippet => snippet.table_data.id)))
		}
	}

	const clearSelection = () => {
		setSelectedSnippets(new Set())
	}

	const getSelectedSnippets = () => {
		return availableSnippets.filter(snippet => 
			selectedSnippets.has(snippet.table_data.id)
		)
	}

	const isAllSelected = selectedSnippets.size === availableSnippets.length && availableSnippets.length > 0

	return {
		selectedSnippets,
		handleSnippetToggle,
		handleSelectAll,
		clearSelection,
		getSelectedSnippets,
		isAllSelected
	}
}
