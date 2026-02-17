import React, { useCallback, useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSelection } from '../../../hooks/useSelection'
import { createContextHook } from '../../../utils/bootstrap'
import { REST_BASES } from '../../../utils/restAPI'
import { isNetworkAdmin } from '../../../utils/screen'
import { useMigrationOptions } from './WithMigrationOptions'
import type { RestAPI } from '../../../hooks/useRestAPI'
import type { UseSelection } from '../../../hooks/useSelection'
import type { PropsWithChildren } from 'react'

export enum MigrationStep {
	LoadImporters,
	FetchSnippets,
	MigrateSnippets
}

export interface MigrationError {
	step: MigrationStep
	message: string
}

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

const makeMigrationRequest = async (api: RestAPI, importer: string, request: ImportRequest): Promise<number[]> => {
	if (0 === request.ids.length) {
		throw new Error(__('Please select snippets to import.', 'code-snippets'))
	}

	if (!importer) {
		throw new Error(__('Please select an importer.', 'code-snippets'))
	}

	const response = await api.post<ImportResponse, ImportRequest>(`${REST_BASES.importPlugins}/${importer}/import`, request)

	if (1 > response.imported.length) {
		throw new Error(__('No snippets were imported.', 'code-snippets'))
	}

	return response.imported
}

const useRemoteRequest = <T, >(
	step: MigrationStep,
	setError: (error: MigrationError | undefined) => void,
	setIsWorking: (step: MigrationStep | undefined) => void
): [T[], (makeRequest: () => Promise<T[]>, onSuccess?: VoidFunction) => void, VoidFunction] => {
	const [data, setData] = useState<T[]>([])

	const clearData = useCallback(() => setData([]), [])

	const requestData = useCallback((makeRequest: () => Promise<T[]>, onSuccess?: VoidFunction) => {
		setError(undefined)
		setIsWorking(step)
		setData([])

		makeRequest()
			.then(response => {
				setData(response)
				onSuccess?.()
			})
			.catch((error: unknown) => {
				setError({ step, message: error instanceof Error ? error.message : 'Unknown error' })
			})
			.finally(() => setIsWorking(undefined))
	}, [setError, setIsWorking, step])

	return [data, requestData, clearData]
}

export interface MigrationDataContext {
	error: MigrationError | undefined
	importSnippets: VoidFunction
	importers: Importer[]
	isWorking: MigrationStep | undefined
	changeSelectedImporter: (newImporter: string) => void
	snippetSelection: UseSelection<ImportableSnippet, ImportableSnippet['id']>
	importedIds: number[]
}

const [Context, useMigrationData] = createContextHook<MigrationDataContext>('useMigrationData')

export const WithMigrationData: React.FC<PropsWithChildren> = ({ children }) => {
	const { api } = useRestAPI()
	const { selectedImporter, setSelectedImporter, autoAddTags, tagValue } = useMigrationOptions()

	const [error, setError] = useState<MigrationError>()
	const [isWorking, setIsWorking] = useState<MigrationStep>()

	const [importedIds, doImport] = useRemoteRequest<number>(MigrationStep.MigrateSnippets, setError, setIsWorking)
	const [importers, fetchImporters] = useRemoteRequest<Importer>(MigrationStep.LoadImporters, setError, setIsWorking)
	const [snippets, fetchSnippets, resetSnippets] =
		useRemoteRequest<ImportableSnippet>(MigrationStep.FetchSnippets, setError, setIsWorking)

	const snippetSelection = useSelection(snippets, snippet => snippet.table_data.id)

	useEffect(() => {
		fetchImporters(() => api
			.get<Record<string, Importer>>(REST_BASES.importPlugins)
			.then(response => Object.values(response)))
	}, [api, fetchImporters])

	useEffect(() => {
		if (selectedImporter) {
			fetchSnippets(() => api.get<ImportableSnippet[]>(`${REST_BASES.importPlugins}/${selectedImporter}`))
		}
	}, [api, selectedImporter, fetchSnippets])

	const importSnippets = () => {
		const request: ImportRequest = {
			ids: Array.from(snippetSelection.selectedItems),
			network: isNetworkAdmin(),
			auto_add_tags: autoAddTags,
			tag_value: autoAddTags ? tagValue : undefined
		}

		const handleImportSuccess = () => {
			resetSnippets()
			snippetSelection.clearSelection()
		}

		doImport(() => makeMigrationRequest(api, selectedImporter, request), handleImportSuccess)
	}

	const changeSelectedImporter = (newImporter: string) => {
		setSelectedImporter(newImporter)
		snippetSelection.clearSelection()
		setError(undefined)
		resetSnippets()
	}

	const value: MigrationDataContext = {
		error,
		snippetSelection,
		importSnippets,
		importers,
		isWorking,
		changeSelectedImporter,
		importedIds
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useMigrationData }
