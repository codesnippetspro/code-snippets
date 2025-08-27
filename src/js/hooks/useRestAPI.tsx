import React, { useMemo } from 'react'
import axios from 'axios'
import { createContextHook } from '../utils/hooks'
import { REST_API_AXIOS_CONFIG } from '../utils/restAPI'
import { buildSnippetsAPI } from '../utils/snippets/api'
import type { SnippetsAPI } from '../utils/snippets/api'
import type { PropsWithChildren } from 'react'
import type { AxiosInstance, AxiosResponse } from 'axios'

export interface RestAPIContext {
	api: RestAPI
	snippetsAPI: SnippetsAPI
	axiosInstance: AxiosInstance
}

export interface RestAPI {
	get: <T>(url: string) => Promise<T>
	post: <T>(url: string, data?: object) => Promise<T>
	put: <T>(url: string, data?: object) => Promise<T>
	del: <T>(url: string) => Promise<T>
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

const buildRestAPI = (axiosInstance: AxiosInstance): RestAPI => ({
	get: <T, >(url: string): Promise<T> =>
		debugRequest('GET', url, axiosInstance.get<T, AxiosResponse<T, never>, never>(url)),

	post: <T, >(url: string, data?: object): Promise<T> =>
		debugRequest('POST', url, axiosInstance.post<T, AxiosResponse<T, typeof data>, typeof data>(url, data), data),

	del: <T, >(url: string): Promise<T> =>
		debugRequest('DELETE', url, axiosInstance.delete<T, AxiosResponse<T, never>, never>(url)),

	put: <T, >(url: string, data?: object): Promise<T> =>
		debugRequest('PUT', url, axiosInstance.put<T, AxiosResponse<T, typeof data>, typeof data>(url, data), data)
})

export const [RestAPIContext, useRestAPI] = createContextHook<RestAPIContext>('RestAPI')

export const WithRestAPIContext: React.FC<PropsWithChildren> = ({ children }) => {
	const axiosInstance = useMemo(() => axios.create(REST_API_AXIOS_CONFIG), [])

	const api = useMemo(() => buildRestAPI(axiosInstance), [axiosInstance])
	const snippetsAPI = useMemo(() => buildSnippetsAPI(api), [api])

	const value: RestAPIContext = { api, snippetsAPI, axiosInstance }

	return <RestAPIContext.Provider value={value}>{children}</RestAPIContext.Provider>
}
