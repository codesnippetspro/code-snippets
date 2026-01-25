import React, { useEffect, useRef, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { REST_NAMESPACED } from '../../../../utils/restAPI'
import { isNetworkAdmin } from '../../../../utils/screen'
import { Button } from '../../../common/Button'
import { ImportCard } from '../../common/ImportCard'
import { useSelection } from '../../../../hooks/useSelection'
import { SnippetSelectionTable } from './SnippetSelectionTable'
import type { ImportResult } from './ImportResultDisplay'
import type { ImportableSnippetSchema } from '../../../../types/schema/ImportableSnippetSchema'
import type { DuplicateAction } from '../SelectFiles/DuplicateActionSelector'

export interface SnippetImportRequest {
	snippets: ImportableSnippetSchema[]
	duplicate_action: 'ignore' | 'replace' | 'skip'
	network?: boolean
}

export interface SnippetImportResponse {
	imported: number
	imported_ids: number[]
	message: string
}

interface ReturnLinkProps {
	onCancel: VoidFunction
	clearSelection: VoidFunction
}

const ReturnLink: React.FC<ReturnLinkProps> = ({ onCancel, clearSelection }) =>
	<div className="return-link">
		<Button link onClick={() => {
			clearSelection()
			onCancel()
		}}>
			{__('← Upload Different Files', 'code-snippets')}
		</Button>
	</div>

interface SelectSnippetsFormProps {
	isImporting: boolean
	availableSnippets: ImportableSnippetSchema[]
	snippetSelection: ReturnType<typeof useSelection<ImportableSnippetSchema>>
}

const SelectSnippetsForm: React.FC<SelectSnippetsFormProps> = ({ availableSnippets, snippetSelection, isImporting }) => {
	const SelectAllButton = () =>
		<Button onClick={snippetSelection.handleSelectAll}>
			{snippetSelection.isAllSelected
				? __('Deselect All', 'code-snippets')
				: __('Select All', 'code-snippets')}
		</Button>

	const SubmitButton = () =>
		<Button type="submit" primary disabled={0 === snippetSelection.selectedItems.size || isImporting}>
			{isImporting
				? __('Importing…', 'code-snippets')
				// translators: %d: number of selected snippets.
				: sprintf(__('Import Selected (%d)', 'code-snippets'), snippetSelection.selectedItems.size)}
		</Button>

	return (
		<>
			<div className="tablenav top">
				<div>
					<h3>{sprintf(
						// translators: %d: number of available snippets.
						__('Available snippets (%d)', 'code-snippets'),
						availableSnippets.length
					)}</h3>
					<p>{__('Select the snippets you would like to import.', 'code-snippets')}</p>
				</div>
				<div className="table-actions">
					<SelectAllButton />
					<SubmitButton />
				</div>
			</div>

			<SnippetSelectionTable snippets={availableSnippets} selection={snippetSelection} />

			<div className="tablenav bottom">
				<SelectAllButton />
				<SubmitButton />
			</div>
		</>
	)
}

export interface SelectSnippetsStepProps {
	onCancel: VoidFunction
	duplicateAction: DuplicateAction
	setImportResult: (result: ImportResult | undefined) => void
	availableSnippets: ImportableSnippetSchema[]
}

export const SelectSnippets: React.FC<SelectSnippetsStepProps> = ({
	onCancel,
	setImportResult,
	duplicateAction,
	availableSnippets
}) => {
	const { api } = useRestAPI()
	const snippetSelection = useSelection<ImportableSnippetSchema>(availableSnippets, snippet => snippet.table_data.id)
	const [isImporting, setIsImporting] = useState(false)

	const selectSectionRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (selectSectionRef.current) {
			selectSectionRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' })
		}
	}, [selectSectionRef])

	const buildRequest = (): SnippetImportRequest | undefined => {
		const snippetsToImport = snippetSelection.getSelectedItems()

		if (0 === snippetsToImport.length) {
			alert(__('Please select snippets to import.', 'code-snippets'))
			return undefined
		}

		return {
			snippets: snippetsToImport,
			duplicate_action: duplicateAction,
			network: isNetworkAdmin()
		}
	}

	const handleImportSelected = () => {
		const request = buildRequest()

		if (request) {
			setIsImporting(true)
			setImportResult(undefined)

			api
				.post<SnippetImportResponse, SnippetImportRequest>(`${REST_NAMESPACED}1/file-upload/import`, request)
				.then(({ message, imported }) => {
					setImportResult({ success: true, message, imported })
				})
				.catch((error: unknown) => {
					console.error('Import error:', error)
					setImportResult({
						success: false,
						message: error instanceof Error ? error.message : __('An unknown error occurred.', 'code-snippets')
					})
				})
				.finally(() => setIsImporting(false))
		}
	}

	return (
		<ImportCard ref={selectSectionRef} className="import-select-card snippets-table-card">
			<form onSubmit={handleImportSelected}>
				<ReturnLink onCancel={onCancel} clearSelection={snippetSelection.clearSelection} />
				<SelectSnippetsForm isImporting={isImporting} snippetSelection={snippetSelection} availableSnippets={availableSnippets} />
			</form>
		</ImportCard>
	)
}
