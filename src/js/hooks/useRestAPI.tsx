import React, { useMemo } from 'react'
import axios from 'axios'
import { createContextHook } from '../utils/bootstrap'
import { REST_API_AXIOS_CONFIG, applyMethodOverride, applyRestNonce, listenForNonceRefresh } from '../utils/restAPI'
import type { PropsWithChildren } from 'react'
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios'

export interface RestAPIContext {
	api: RestAPI
	axiosInstance: AxiosInstance
}

export interface RestAPI {
	get: <T>(url: string) => Promise<T>
	getResponse: <T>(url: string) => Promise<AxiosResponse<T>>
	post: <T, D = never>(url: string, data?: D, config?: AxiosRequestConfig<D>) => Promise<T>
	put: <T>(url: string, data?: object) => Promise<T>
	del: <T>(url: string) => Promise<T>
}

const debugRequest = async <T, D = never>(
	method: 'GET' | 'POST' | 'PUT' | 'DELETE',
	url: string,
	doRequest: Promise<AxiosResponse<T, D>>,
	data?: D
): Promise<AxiosResponse<T>> => {
	if (window.CODE_SNIPPETS?.debug) {
		console.debug(`${method} ${url}`, ...data ? [data] : [])
		const response = await doRequest
		console.debug('Response', response)
		return response
	} else {
		return await doRequest
	}
}

const buildRestAPI = (axiosInstance: AxiosInstance): RestAPI => ({
	getResponse: <T, >(url: string): Promise<AxiosResponse<T>> =>
		debugRequest('GET', url, axiosInstance.get<T, AxiosResponse<T, never>, never>(url)),

	get: <T, >(url: string): Promise<T> =>
		debugRequest('GET', url, axiosInstance.get<T, AxiosResponse<T, never>, never>(url))
			.then(response => response.data),

	post: <T, D = never>(url: string, data?: D, config?: AxiosRequestConfig<D>): Promise<T> =>
		debugRequest('POST', url, axiosInstance.post<T, AxiosResponse<T>>(url, data, config), data)
			.then(response => response.data),

	del: <T, >(url: string): Promise<T> =>
		debugRequest('DELETE', url, axiosInstance.delete<T, AxiosResponse<T, never>, never>(url))
			.then(response => response.data),

	put: <T, >(url: string, data?: object): Promise<T> =>
		debugRequest('PUT', url, axiosInstance.put<T, AxiosResponse<T>>(url, data), data)
			.then(response => response.data),
})

const [Context, useRestAPI] = createContextHook<RestAPIContext>('useRestAPI')

export const WithRestAPIContext: React.FC<PropsWithChildren> = ({ children }) => {
	const axiosInstance = useMemo(() => {
		const instance = axios.create(REST_API_AXIOS_CONFIG)
		instance.interceptors.request.use(applyRestNonce)
		instance.interceptors.request.use(applyMethodOverride)
		listenForNonceRefresh()
		return instance
	}, [])

	const api = useMemo(() => buildRestAPI(axiosInstance), [axiosInstance])
	const value: RestAPIContext = { api, axiosInstance }

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useRestAPI }
