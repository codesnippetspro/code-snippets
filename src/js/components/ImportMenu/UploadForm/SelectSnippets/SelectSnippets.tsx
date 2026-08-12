import React, { useEffect, useRef, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { unpackErrorResponse } from '../../../../utils/errors'
import { REST_BASES } from '../../../../utils/restAPI'
import { isNetworkAdmin } from '../../../../utils/screen'
import { Button } from '../../../common/Button'
import { ImportCard } from '../../common/ImportCard'
import { useSelection } from '../../../../hooks/useSelection'
import { SnippetSelectionTable } from './SnippetSelectionTable'
import type { FormEventHandler, ReactNode } from 'react'
import type { UseSelection } from '../../../../hooks/useSelection'
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

interface SelectAllButtonProps {
	selectAll: VoidFunction
	isAllSelected: boolean
}

const SelectAllButton: React.FC<SelectAllButtonProps> = ({ selectAll, isAllSelected }) =>
	<Button onClick={selectAll}>
		{isAllSelected
			? __('Deselect All', 'code-snippets')
			: __('Select All', 'code-snippets')}
	</Button>

interface SubmitButtonProps {
	isImporting: boolean
	selectedCount: number
}

const SubmitButton: React.FC<SubmitButtonProps> = ({ isImporting, selectedCount }) =>
	<Button type="submit" primary disabled={0 === selectedCount || isImporting}>
		{isImporting
			? __('Importing…', 'code-snippets')
			// translators: %d: number of selected snippets.
			: sprintf(__('Import Selected (%d)', 'code-snippets'), selectedCount)}
	</Button>

interface SelectSnippetsFormProps {
	isImporting: boolean
	availableSnippets: ImportableSnippetSchema[]
	snippetSelection: UseSelection<ImportableSnippetSchema, ImportableSnippetSchema['table_data']['id']>
}

const SelectSnippetsForm: React.FC<SelectSnippetsFormProps> = ({ availableSnippets, snippetSelection, isImporting }) =>
	<>
		<div className="tablenav top">
			<div>
				<h2>{// translators: %d: number of available snippets.
					sprintf(__('Available snippets (%d)', 'code-snippets'), availableSnippets.length)}</h2>
				<p>{__('Select the snippets you would like to import.', 'code-snippets')}</p>
			</div>
			<div className="table-actions">
				<SelectAllButton selectAll={snippetSelection.selectAll} isAllSelected={snippetSelection.isAllSelected} />
				<SubmitButton isImporting={isImporting} selectedCount={snippetSelection.selectedItems.size} />
			</div>
		</div>

		<SnippetSelectionTable snippets={availableSnippets} selection={snippetSelection} />

		<div className="tablenav bottom">
			<SelectAllButton selectAll={snippetSelection.selectAll} isAllSelected={snippetSelection.isAllSelected} />
			<SubmitButton isImporting={isImporting} selectedCount={snippetSelection.selectedItems.size} />
		</div>
	</>

interface SubmitFormProps {
	children: ReactNode
	duplicateAction: DuplicateAction
	setImportResult: (result: ImportResult | undefined) => void
	snippetSelection: UseSelection<ImportableSnippetSchema, ImportableSnippetSchema['table_data']['id']>
	setIsImporting: (isImporting: boolean) => void
}

const SubmitForm: React.FC<SubmitFormProps> = ({ children, duplicateAction, setImportResult, setIsImporting, snippetSelection }) => {
	const { api } = useRestAPI()

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

	const handleImportSelected: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
		const request = buildRequest()

		if (request) {
			setIsImporting(true)
			setImportResult(undefined)

			api
				.post<SnippetImportResponse, SnippetImportRequest>(`${REST_BASES.importFiles}/import`, request)
				.then(({ message, imported }) => {
					setImportResult({ step: 'select', success: true, message, imported })
				})
				.catch((error: unknown) => {
					console.error('Import error:', error)
					setImportResult({ step: 'select', success: false, message: unpackErrorResponse(error) })
				})
				.finally(() => setIsImporting(false))
		}
	}

	return <form onSubmit={handleImportSelected}>{children}</form>
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
	const snippetSelection = useSelection(availableSnippets, snippet => snippet.table_data.id)
	const [isImporting, setIsImporting] = useState(false)

	const selectSectionRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (selectSectionRef.current) {
			selectSectionRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' })
		}
	}, [selectSectionRef])

	return (
		<ImportCard ref={selectSectionRef} className="import-select-card snippets-table-card">
			<SubmitForm {...{ duplicateAction, snippetSelection, setImportResult, setIsImporting }}>
				<ReturnLink onCancel={onCancel} clearSelection={snippetSelection.clearSelection} />
				<SelectSnippetsForm isImporting={isImporting} snippetSelection={snippetSelection} availableSnippets={availableSnippets} />
			</SubmitForm>
		</ImportCard>
	)
}
