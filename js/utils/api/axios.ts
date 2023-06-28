import { useMemo } from 'react'
import axios, { AxiosInstance, AxiosResponse, CreateAxiosDefaults } from 'axios'

export interface AxiosAPI {
	get: <T>(url: string) => Promise<AxiosResponse<T, never>>
	post: <T, D>(url: string, data?: D) => Promise<AxiosResponse<T, D>>
	del: <T>(url: string) => Promise<AxiosResponse<T, never>>
	axiosInstance: AxiosInstance
}

const debugResponseHandler = <T>(response: T) => {
	console.debug('Response', response)
	return response
}

export const useAxios = (defaultConfig: CreateAxiosDefaults): AxiosAPI => {
	const axiosInstance = useMemo(() => axios.create(defaultConfig), [defaultConfig])

	return useMemo((): AxiosAPI => ({
		get: <T>(url: string) => {
			console.debug(`GET ${url}`)
			return axiosInstance.get<T, AxiosResponse<T, never>, never>(url)
				.then(debugResponseHandler)
		},

		post: <T, D>(url: string, data?: D) => {
			console.debug(`POST ${url}`, data)
			return axiosInstance.post<T, AxiosResponse<T, D>, D>(url, data)
				.then(debugResponseHandler)
		},

		del: <T>(url: string) => {
			console.debug(`DELETE ${url}`)
			return axiosInstance.delete<T, AxiosResponse<T, never>, never>(url)
				.then(debugResponseHandler)
		},

		axiosInstance
	}), [axiosInstance])
}
