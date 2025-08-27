import { useMemo } from 'react'
import { REST_CLOUD_BASE } from '../utils/restAPI'
import { useRestAPI } from './useRestAPI'
import type { Snippet, SnippetType } from '../types/Snippet'

export type ExplainSnippetFields = keyof Pick<Snippet, 'code' | 'desc' | 'tags'>

export interface GeneratedSnippet {
	name?: string
	code?: string
	desc?: string
}

export interface ExplainedSnippet {
	name?: string
	lines?: Record<number, string>[]
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
	const { api: { post } } = useRestAPI()

	return useMemo((): GenerativeAPI => ({
		generateSnippet: (prompt, type) =>
			post<ApiResponse<GeneratedSnippet>>(`${REST_CLOUD_BASE}/ai/prompt`, { prompt, type })
				.then(response => response.message),

		explainSnippet: (code, field) =>
			post<ApiResponse<ExplainedSnippet>>(`${REST_CLOUD_BASE}/ai/explain`, { code, field })
				.then(response => response.message)
	}), [post])
}
