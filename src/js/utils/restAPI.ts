import axios from 'axios'
import { trimLeadingChar, trimTrailingChar } from './text'
import type { AxiosRequestConfig, AxiosResponse } from 'axios'

const REST_BASE = window.CODE_SNIPPETS?.restAPI.base ?? ''

const getRestUrl = (endpoint: string): string =>
	`${trimTrailingChar(REST_BASE, '/')}/${trimLeadingChar(endpoint, '/')}`

const GET_CACHE: Record<string, AxiosResponse<unknown> | undefined> = {}

export const getCached = <T, D>(endpoint: string, refresh = false, config?: AxiosRequestConfig<D>): Promise<AxiosResponse<T, D>> =>
	!refresh && GET_CACHE[endpoint]
		? Promise.resolve(<AxiosResponse<T, D>> GET_CACHE[endpoint])
		: axios
			.get<T, AxiosResponse<T, D>, D>(getRestUrl(endpoint), config)
			.then(response => {
				GET_CACHE[endpoint] = response
				return response
			})
