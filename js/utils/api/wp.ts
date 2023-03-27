import axios, { AxiosResponse } from 'axios'

const REST_BASE = window.CODE_SNIPPETS_EDIT?.restAPI.base ?? ''

const trimLeadingSlash = (path: string) =>
	'/' === path.charAt(0) ? path.substring(1) : path

const trimTrailingSlash = (path: string) =>
	'/' === path.charAt(path.length - 1) ? path.substring(0, path.length - 1) : path

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
