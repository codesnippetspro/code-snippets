import { __ } from '@wordpress/i18n'
import { isAxiosError, isCancel } from 'axios'

export const handleUnknownError = (error: unknown) => {
	console.error(error)
}

/**
 * Whether a rejection came from the caller aborting the request rather than
 * from a failure — axios rejects cancelled requests with no response attached,
 * so these must not be surfaced to the user as errors.
 */
export const isAbortError = (error: unknown): boolean =>
	isCancel(error) || error instanceof DOMException && 'AbortError' === error.name

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
 * Whether a request finished without the browser receiving a response.
 *
 * Nothing can be concluded about the write itself from this: the request may
 * never have arrived, or it may have been handled and the response lost on the
 * way back. Aborts are excluded, as the caller stopped those deliberately.
 */
export const isUnconfirmedRequest = (error: unknown): boolean =>
	isAxiosError(error) && !error.response && !isAbortError(error)

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
			'No response came back, so this could not be confirmed. The change may already have been saved — ' +
			'check in another tab before trying again.',
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
