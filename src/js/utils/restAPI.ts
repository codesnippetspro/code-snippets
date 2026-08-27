import { trimTrailingChar } from './text'
import type { AxiosRequestConfig, InternalAxiosRequestConfig } from 'axios'

const normalizeUrl = (url: string | undefined) =>
	trimTrailingChar(url ?? '', '/')

export const REST_BASES = {
	snippets: normalizeUrl(window.CODE_SNIPPETS?.restAPI.snippets),
	recentlyActive: normalizeUrl(window.CODE_SNIPPETS?.restAPI.recentlyActive),
	preferences: normalizeUrl(window.CODE_SNIPPETS?.restAPI.preferences),
	importPlugins: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importPlugins),
	importFiles: normalizeUrl(window.CODE_SNIPPETS?.restAPI.importFiles),
	cloud: {
		snippets: normalizeUrl(window.CODE_SNIPPETS?.restAPI.cloud.snippets),
	}
}

/** Verbs that hosts and firewalls commonly reject outright. */
const OVERRIDDEN_METHODS = ['delete', 'put', 'patch']

/**
 * Send write requests as POST, naming the intended verb in a header.
 *
 * Plenty of hosts allow only GET and POST, so a DELETE never reaches
 * WordPress: the request is rejected upstream, and the browser reports a 403 —
 * or a severed connection — that no amount of correct authentication can fix.
 * The REST server reads `X-HTTP-Method-Override` on a POST and dispatches the
 * route exactly as it would have, so this changes nothing WordPress sees while
 * letting the request through.
 */
export const applyMethodOverride = (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
	const method = config.method?.toLowerCase()

	if (!method || !OVERRIDDEN_METHODS.includes(method)) {
		return config
	}

	config.headers.set('X-HTTP-Method-Override', method.toUpperCase())
	config.method = 'post'

	return config
}

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'X-WP-Nonce': window.CODE_SNIPPETS?.restAPI.nonce,
		'Access-Control': window.CODE_SNIPPETS?.restAPI.cloud.token
	}
}
