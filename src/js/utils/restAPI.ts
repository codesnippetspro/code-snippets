import { trimTrailingChar } from './text'
import type { AxiosRequestConfig } from 'axios'

const normalizeUrl = (url: string | undefined) =>
	trimTrailingChar(url ?? '', '/')

export const REST_BASES = {
	snippets: normalizeUrl(window.CODE_SNIPPETS?.restAPI.snippets),
	recentlyActive: normalizeUrl(window.CODE_SNIPPETS?.restAPI.recentlyActive),
	import: {
		plugins: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importPlugins),
		files: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importFiles),
	},
	preferences: {
		snippetView: normalizeUrl(window.CODE_SNIPPETS?.restAPI.snippetView),
		insights: normalizeUrl(window.CODE_SNIPPETS?.restAPI.insightsView),
		demosSeen: normalizeUrl(window.CODE_SNIPPETS?.restAPI.demosSeen),
	},
	cloud: {
		snippets: normalizeUrl(window.CODE_SNIPPETS?.restAPI.cloud.snippets),
	}
}

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.cloud.token
	}
}
