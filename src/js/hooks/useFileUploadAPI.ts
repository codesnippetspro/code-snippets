import { useMemo } from 'react'
import { useAxios } from './useAxios'
import type { AxiosResponse, CreateAxiosDefaults } from 'axios'

export interface FileUploadRequest {
	files: FileList
}

export interface FileParseResponse {
	snippets: ImportableSnippet[]
	total_count: number
	message: string
	warnings?: string[]
}

export interface ImportableSnippet {
	id?: number
	name: string
	desc?: string
	description?: string
	code: string
	tags?: string[]
	scope?: string
	source_file?: string
	table_data: {
		id: number | string
		title: string
		scope: string
		tags: string
		description: string
		type: string
	}
}

export interface SnippetImportRequest {
	snippets: ImportableSnippet[]
	duplicate_action: 'ignore' | 'replace' | 'skip'
	network?: boolean
}

export interface SnippetImportResponse {
	imported: number
	imported_ids: number[]
	message: string
}

const ROUTE_BASE = `${window.CODE_SNIPPETS?.restAPI.base}code-snippets/v1/`

const AXIOS_CONFIG: CreateAxiosDefaults = {
	headers: { 'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce }
}

export interface FileUploadAPI {
	parseFiles: (request: FileUploadRequest) => Promise<AxiosResponse<FileParseResponse>>
	importSnippets: (request: SnippetImportRequest) => Promise<AxiosResponse<SnippetImportResponse>>
}

export const useFileUploadAPI = (): FileUploadAPI => {
	const { axiosInstance } = useAxios(AXIOS_CONFIG)

	return useMemo((): FileUploadAPI => ({
		parseFiles: (request: FileUploadRequest) => {
			const formData = new FormData()
			
			for (let i = 0; i < request.files.length; i++) {
				formData.append('files[]', request.files[i])
			}

			return axiosInstance.post<FileParseResponse>(
				`${ROUTE_BASE}file-upload/parse`, 
				formData,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					}
				}
			)
		},
		
		importSnippets: (request: SnippetImportRequest) => {
			return axiosInstance.post<SnippetImportResponse>(
				`${ROUTE_BASE}file-upload/import`,
				request
			)
		}
	}), [axiosInstance])
}
