import axios, { AxiosResponse } from 'axios'

const REST_BASE = window.CODE_SNIPPETS?.restAPI.base ?? ''

export const trimLeadingSlash = (path: string): string =>
	'/' === path.charAt(0) ? path.slice(1) : path

export const trimTrailingSlash = (path: string): string =>
	'/' === path.charAt(path.length - 1) ? path.slice(0, -1) : path

const getRestUrl = (endpoint: string): string =>
	`${trimTrailingSlash(REST_BASE)}/${trimLeadingSlash(endpoint)}`

const GET_CACHE: Record<string, AxiosResponse> = {}

export const apiGet = <T>(endpoint: string, refresh = false): Promise<AxiosResponse<T>> =>
	!refresh && GET_CACHE[endpoint] ?
		Promise.resolve(GET_CACHE[endpoint]) :
		axios.get<T>(getRestUrl(endpoint))
			.then(response => {
				GET_CACHE[endpoint] = response
				return response
			})
