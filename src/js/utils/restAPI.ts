import { trimLeadingChar, trimTrailingChar } from './text'

const REST_BASE = window.CODE_SNIPPETS?.restAPI.base ?? ''

export const getRestUrl = (endpoint: string): string =>
	`${trimTrailingChar(REST_BASE, '/')}/${trimLeadingChar(endpoint, '/')}`
