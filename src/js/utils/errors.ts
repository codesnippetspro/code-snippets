import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'

export const handleUnknownError = (error: unknown) => {
	console.error(error)
}

export const unpackErrorResponse = (error: unknown): string => {
	if (isAxiosError(error)) {
		if (error.response) {
			const responseData: unknown = error.response.data

			if (responseData && 'object' === typeof responseData && 'message' in responseData) {
				return String(responseData.message)
			}
		}

		return error.message
	}

	return __('An unknown error occurred.', 'code-snippets')
}

/**
 * Explain a failed request in terms the reader can act on.
 *
 * An expired session is the common case worth naming: the snippet editor is a
 * screen people leave open, and once the session lapses WordPress rejects every
 * write with a 403 that says only "Cookie check failed". Reporting the raw
 * status left people believing the plugin had ignored them.
 */
export const describeRequestError = (error: unknown): string => {
	if (!isAxiosError(error)) {
		return unpackErrorResponse(error)
	}

	if (!error.response) {
		return __(
			'The request did not reach your site. Check your connection, or whether a security plugin is blocking it.',
			'code-snippets'
		)
	}

	const data: unknown = error.response.data
	const code = data && 'object' === typeof data && 'code' in data ? String(data.code) : ''

	if ('rest_cookie_invalid_nonce' === code || 'rest_not_logged_in' === code) {
		return __(
			'You have been signed out, so nothing was saved. Sign in again in another tab, then save. Your changes are still here.',
			'code-snippets'
		)
	}

	return unpackErrorResponse(error)
}
