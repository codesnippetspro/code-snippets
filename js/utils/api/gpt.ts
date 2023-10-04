import { AxiosRequestConfig } from 'axios'
import { useMemo } from 'react'
import { useAxios } from './axios'

const ROUTE_BASE = window.CODE_SNIPPETS?.restAPI.cloud

const AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.localToken
	}
}

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
	generateSnippet: (prompt: string) => Promise<GeneratedSnippet>
	explainSnippet: (code: string) => Promise<ExplainedSnippet>
}

export const useGenerativeAPI = (): GenerativeAPI => {
	const { post } = useAxios(AXIOS_CONFIG)

	return useMemo((): GenerativeAPI => ({
		generateSnippet: prompt =>
			post<ApiResponse<GeneratedSnippet>, { prompt: string }>(
				`${ROUTE_BASE}/ai/prompt`,
				{ prompt }
			)
				.then(response => response.data.message),

		explainSnippet: code =>
			post<ApiResponse<ExplainedSnippet>, { prompt: string }>(
				`${ROUTE_BASE}/ai/explain`,
				{ prompt: code }
			)
				.then(response => response.data.message)
	}), [post])
}
