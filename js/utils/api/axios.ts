import { useMemo } from 'react'
import axios, { AxiosInstance, AxiosResponse, CreateAxiosDefaults } from 'axios'

export interface AxiosAPI {
	get: <T>(url: string) => Promise<AxiosResponse<T, never>>
	post: <T, D>(url: string, data?: D) => Promise<AxiosResponse<T, D>>
	del: <T>(url: string) => Promise<AxiosResponse<T, never>>
	axiosInstance: AxiosInstance
}

const debugRequest = async <T, D = never>(
	method: 'GET' | 'POST' | 'PUT' | 'DELETE',
	url: string,
	doRequest: Promise<AxiosResponse<T, D>>,
	data?: D
): Promise<AxiosResponse<T, D>> => {
	console.debug(`${method} ${url}`, ...data ? [data] : [])
	const response = await doRequest
	console.debug('Response', response)
	return response
}

export const useAxios = (defaultConfig: CreateAxiosDefaults): AxiosAPI => {
	const axiosInstance = useMemo(() => axios.create(defaultConfig), [defaultConfig])

	return useMemo((): AxiosAPI => ({
		get: <T>(url: string): Promise<AxiosResponse<T, never>> =>
			debugRequest('GET', url, axiosInstance.get<T, AxiosResponse<T, never>, never>(url)),

		post: <T, D>(url: string, data?: D) =>
			debugRequest('POST', url, axiosInstance.post<T, AxiosResponse<T, D>, D>(url, data), data),

		del: <T>(url: string) =>
			debugRequest('DELETE', url, axiosInstance.delete<T, AxiosResponse<T, never>, never>(url)),

		axiosInstance
	}), [axiosInstance])
}
