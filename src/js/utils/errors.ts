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
