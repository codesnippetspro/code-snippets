import { trimTrailingChar } from './text'
import type { AxiosRequestConfig } from 'axios'

const normalizeUrl = (url: string | undefined) =>
	trimTrailingChar(url ?? '', '/')

export const REST_BASE = normalizeUrl(window.CODE_SNIPPETS?.restAPI.base)
export const REST_NAMESPACED = normalizeUrl(window.CODE_SNIPPETS?.restAPI.namespaced)
export const REST_SNIPPETS_BASE = normalizeUrl(window.CODE_SNIPPETS?.restAPI.snippets)
export const REST_CLOUD_SEARCH_BASE = normalizeUrl(window.CODE_SNIPPETS?.restAPI.cloudSearch)

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.localToken
	}
}
