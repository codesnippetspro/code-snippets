import axios, { AxiosResponse } from 'axios'
import { trimLeadingChar, trimTrailingChar } from '../general'

const REST_BASE = window.CODE_SNIPPETS?.restAPI.base ?? ''

const getRestUrl = (endpoint: string): string =>
	`${trimTrailingChar(REST_BASE, '/')}/${trimLeadingChar(endpoint, '/')}`

const GET_CACHE: Record<string, AxiosResponse> = {}

export const getCached = <T>(endpoint: string, refresh = false): Promise<AxiosResponse<T>> =>
	!refresh && GET_CACHE[endpoint] ?
		Promise.resolve(GET_CACHE[endpoint]) :
		axios.get<T>(getRestUrl(endpoint))
			.then(response => {
				GET_CACHE[endpoint] = response
				return response
			})
