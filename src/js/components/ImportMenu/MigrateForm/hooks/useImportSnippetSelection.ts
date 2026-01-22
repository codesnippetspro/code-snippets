import { useState } from 'react'
import type { ImportableSnippet } from '../../../../hooks/useImportersAPI'

export interface UseImportSnippetSelection {
	selectedSnippets: Set<number>
	handleSnippetToggle: (snippetId: number) => void
	handleSelectAll: VoidFunction
	clearSelection: VoidFunction
	getSelectedSnippets: () => ImportableSnippet[]
	isAllSelected: boolean
}

export const useImportSnippetSelection = (availableSnippets: ImportableSnippet[]): UseImportSnippetSelection => {
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

	const isAllSelected = selectedSnippets.size === availableSnippets.length && 0 < availableSnippets.length

	return {
		selectedSnippets,
		handleSnippetToggle,
		handleSelectAll,
		clearSelection,
		getSelectedSnippets,
		isAllSelected
	}
}
