

import { useCallback, useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from './useRestAPI'

export interface LoadingStatus {
	isLoading: boolean
	errorMessage: string | undefined
}

export interface UseFetchData<T> extends LoadingStatus {
	data: T | undefined
	refreshData: () => Promise<void>
}

export const useFetchData = <T>(url: string): UseFetchData<T> => {
	const { api } = useRestAPI()
	const [data, setData] = useState<T>()
	const [isLoading, setIsLoading] = useState(true)
	const [errorMessage, setErrorMessage] = useState<string>()

	const refreshData = useCallback(async () => {
		setIsLoading(true)
		setErrorMessage(undefined)

		try {
			setData(await api.get<T>(url))
		} catch (error: unknown) {
			setErrorMessage('string' === typeof error && error ? error : __('Unknown error', 'code-snippets'))
		} finally {
			setIsLoading(false)
		}
	}, [api, url])

	useEffect(() => {
		void refreshData()
	}, [refreshData])

	return { data, isLoading, errorMessage, refreshData }
}
