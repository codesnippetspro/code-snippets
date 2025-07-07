import { AxiosRequestConfig } from 'axios'
import { useMemo } from 'react'
import { Snippet, SnippetType } from '../types/Snippet'
import { useAxios } from './useAxios'

const ROUTE_BASE = window.CODE_SNIPPETS?.restAPI.cloud

const AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.localToken
	}
}

export type ExplainSnippetFields = keyof Pick<Snippet, 'code' | 'desc' | 'tags'>

export interface GeneratedSnippet {
	name?: string
	code?: string
	desc?: string
}

export interface ExplainedSnippet {
	name?: string
	lines?: Record<string, string>
	desc?: string
	tags?: string[]
}

interface ApiResponse<T> {
	success: boolean
	message: T
}

export interface GenerativeAPI {
	generateSnippet: (prompt: string, type: SnippetType) => Promise<GeneratedSnippet>
	explainSnippet: (code: string, field: ExplainSnippetFields) => Promise<ExplainedSnippet>
}

export const useGenerativeAPI = (): GenerativeAPI => {
	const { post } = useAxios(AXIOS_CONFIG)

	return useMemo((): GenerativeAPI => ({
		generateSnippet: (prompt, type) =>
			post<ApiResponse<GeneratedSnippet>, { prompt: string, type: SnippetType }>(
				`${ROUTE_BASE}/ai/prompt`,
				{ prompt, type }
			)
				.then(response => response.data.message),

		explainSnippet: (code, field) =>
			post<ApiResponse<ExplainedSnippet>, { code: string, field: ExplainSnippetFields }>(
				`${ROUTE_BASE}/ai/explain`,
				{ code, field }
			)
				.then(response => response.data.message)
	}), [post])
}
