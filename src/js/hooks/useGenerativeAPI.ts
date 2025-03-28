import { useMemo } from 'react'
import { REST_API_AXIOS_CONFIG, REST_CLOUD_BASE } from '../utils/restAPI'
import { useAxios } from './useAxios'
import type { Snippet, SnippetType } from '../types/Snippet'

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
	const { post } = useAxios(REST_API_AXIOS_CONFIG)

	return useMemo((): GenerativeAPI => ({
		generateSnippet: (prompt, type) =>
			post<ApiResponse<GeneratedSnippet>>(`${REST_CLOUD_BASE}/ai/prompt`, { prompt, type })
				.then(response => response.message),

		explainSnippet: (code, field) =>
			post<ApiResponse<ExplainedSnippet>>(`${REST_CLOUD_BASE}/ai/explain`, { code, field })
				.then(response => response.message)
	}), [post])
}
