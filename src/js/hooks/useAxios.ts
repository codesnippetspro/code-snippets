import { useMemo } from 'react'
import axios from 'axios'
import type { AxiosInstance, AxiosResponse, CreateAxiosDefaults } from 'axios'

export interface AxiosAPI {
	get: <T>(url: string) => Promise<T>
	post: <T>(url: string, data?: object) => Promise<T>
	put: <T>(url: string, data?: object) => Promise<T>
	del: <T>(url: string) => Promise<T>
	axiosInstance: AxiosInstance
}

const debugRequest = async <T, D = never>(
	method: 'GET' | 'POST' | 'PUT' | 'DELETE',
	url: string,
	doRequest: Promise<AxiosResponse<T, D>>,
	data?: D
): Promise<T> => {
	console.debug(`${method} ${url}`, ...data ? [data] : [])
	const response = await doRequest
	console.debug('Response', response)
	return response.data
}

export const useAxios = (defaultConfig: CreateAxiosDefaults): AxiosAPI => {
	const axiosInstance = useMemo(() => axios.create(defaultConfig), [defaultConfig])

	return useMemo((): AxiosAPI => ({
		get: <T>(url: string): Promise<T> =>
			debugRequest('GET', url, axiosInstance.get<T, AxiosResponse<T, never>, never>(url)),

		post: <T>(url: string, data?: object): Promise<T> =>
			debugRequest('POST', url, axiosInstance.post<T, AxiosResponse<T, typeof data>, typeof data>(url, data), data),

		del: <T>(url: string): Promise<T> =>
			debugRequest('DELETE', url, axiosInstance.delete<T, AxiosResponse<T, never>, never>(url)),

		put: <T>(url: string, data?: object): Promise<T> =>
			debugRequest('PUT', url, axiosInstance.put<T, AxiosResponse<T, typeof data>, typeof data>(url, data), data),

		axiosInstance
	}), [axiosInstance])
}
