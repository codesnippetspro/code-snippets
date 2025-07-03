import { trimTrailingChar } from './text'
import type { AxiosRequestConfig } from 'axios'

export const REST_BASE = trimTrailingChar(window.CODE_SNIPPETS?.restAPI.base ?? '', '/')
export const REST_SNIPPETS_BASE = trimTrailingChar(window.CODE_SNIPPETS?.restAPI.snippets ?? '', '/')

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.localToken
	}
}
