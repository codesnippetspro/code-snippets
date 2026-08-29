import { __, sprintf } from '@wordpress/i18n'
import { isAxiosError } from 'axios'

const HTTP_FORBIDDEN = 403
const HTTP_NOT_FOUND = 404
const HTTP_SERVER_ERROR = 500

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
 * Describe a failed request in terms the person reading it can act on.
 *
 * The HTTP status is deliberately included. Requests to the snippets API are
 * blocked by security rules and firewalls often enough that "it did nothing"
 * is impossible to diagnose without it, and asking people to open developer
 * tools is a poor substitute for the plugin simply saying what happened.
 */
export const describeError = (error: unknown): string => {
	if (isAxiosError(error)) {
		if (!error.response) {
			return __(
				'The request did not reach your site. It may have been blocked by a firewall or security plugin.',
				'code-snippets'
			)
		}

		const status = error.response.status
		const message = unpackErrorResponse(error)

		if (HTTP_FORBIDDEN === status) {
			return sprintf(
				/* translators: %s: error message returned by the site. */
				__('Your site refused the request (403). Try reloading the page, and check whether a security plugin is blocking it. %s', 'code-snippets'),
				message
			)
		}

		if (HTTP_NOT_FOUND === status) {
			return __(
				'The snippets API could not be found (404). It may be disabled or blocked on your site.',
				'code-snippets'
			)
		}

		if (HTTP_SERVER_ERROR <= status) {
			return sprintf(
				/* translators: 1: HTTP status code, 2: error message returned by the site. */
				__('Your site returned an error (%1$d). %2$s', 'code-snippets'),
				status,
				message
			)
		}

		return sprintf(
			/* translators: 1: HTTP status code, 2: error message returned by the site. */
			__('Your site returned %1$d. %2$s', 'code-snippets'),
			status,
			message
		)
	}

	return unpackErrorResponse(error)
}
