import { trimTrailingChar } from './text'
import type { AxiosRequestConfig } from 'axios'

const normalizeUrl = (url: string | undefined) =>
	trimTrailingChar(url ?? '', '/')

export const REST_BASES = {
	snippets: normalizeUrl(window.CODE_SNIPPETS?.restAPI.snippets),
	cloud: normalizeUrl(window.CODE_SNIPPETS?.restAPI.cloud),
	recentlyActive: normalizeUrl(window.CODE_SNIPPETS?.restAPI.recentlyActive),
	importPlugins: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importPlugins),
	importFiles: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importFiles)
}

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.localToken
	}
}
