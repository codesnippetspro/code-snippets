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

/**
 * The REST nonce to authenticate the next request with.
 *
 * Held in a variable rather than baked into the axios config, because the value
 * the page was rendered with does not stay valid. A nonce expires with the
 * session, and the snippet editor is a screen people leave open for a long
 * time. Once it lapsed, every save failed with a 403 and the only cure was
 * reloading the page, which loses whatever was being written.
 */
let restNonce = window.CODE_SNIPPETS?.restAPI.nonce

/**
 * Keep the REST nonce current for as long as the page is open.
 *
 * WordPress already sends a freshly minted nonce with every Heartbeat response,
 * from `wp_refresh_heartbeat_nonces()`. Core applies it to `wpApiSettings`,
 * which our screens do not enqueue, so the value went unused. Listening for the
 * tick ourselves means an editor left open stays able to save.
 */
export const listenForNonceRefresh = () => {
	// Heartbeat also fires the tick through the hooks API, which avoids
	// depending on jQuery being present and typed.
	window.wp.hooks?.addAction(
		'heartbeat.tick',
		'code-snippets/refresh-rest-nonce',
		(data: { rest_nonce?: string }) => {
			if (data.rest_nonce) {
				restNonce = data.rest_nonce
			}
		}
	)
}

/**
 * Attach the current nonce to an outgoing request.
 *
 * Read per request, so that a nonce refreshed since page load is actually used.
 */
export const applyRestNonce = (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
	if (restNonce) {
		config.headers.set('X-WP-Nonce', restNonce)
	}

	return config
}

export const REST_API_AXIOS_CONFIG: AxiosRequestConfig = {
	headers: {
		'Access-Control': window.CODE_SNIPPETS?.restAPI.cloud.token
	}
}
