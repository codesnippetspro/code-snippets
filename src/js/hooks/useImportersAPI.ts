import { useMemo } from 'react'
import { useAxios } from './useAxios'
import type { AxiosResponse, CreateAxiosDefaults } from 'axios'

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

const ROUTE_BASE = `${window.CODE_SNIPPETS?.restAPI.base}code-snippets/v1/`

const AXIOS_CONFIG: CreateAxiosDefaults = {
	headers: { 'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce }
}

export interface ImportersAPI {
	fetchAll: () => Promise<AxiosResponse<Importer[]>>
	fetchSnippets: (importerName: string) => Promise<AxiosResponse<ImportableSnippet[]>>
	importSnippets: (importerName: string, request: ImportRequest) => Promise<AxiosResponse<ImportResponse>>
}

export const useImportersAPI = (): ImportersAPI => {
	const { get, post } = useAxios(AXIOS_CONFIG)

	return useMemo((): ImportersAPI => ({
		fetchAll: () => get<Importer[]>(`${ROUTE_BASE}importers`),
		fetchSnippets: (importerName: string) => get<ImportableSnippet[]>(`${ROUTE_BASE}${importerName}`),
		importSnippets: (importerName: string, request: ImportRequest) => 
			post<ImportResponse, ImportRequest>(`${ROUTE_BASE}${importerName}/import`, request)
	}), [get, post])
}
