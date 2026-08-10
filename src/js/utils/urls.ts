import { addQueryArgs } from '@wordpress/url'

export type UrlQueryArgs = Record<string, boolean | number | string | undefined | null>

export const fetchQueryParam = (name: string): string | undefined => {
	const urlParams = new URLSearchParams(window.location.search)
	return urlParams.get(name) ?? undefined
}

export const fetchConstQueryParam = <T extends string>(name: string, values: readonly T[]): T | undefined => {
	const isT = (value: string): value is T =>
		(<readonly string[]> values).includes(value)

	const value = fetchQueryParam(name)
	return value && isT(value) ? value : undefined
}

export const updateQueryParams = (params: Record<string, string | number | undefined>) => {
	if ('URLSearchParams' in window) {
		const searchParams = new URLSearchParams(window.location.search)

		for (const [name, value] of Object.entries(params)) {
			if (value) {
				searchParams.set(name, String(value))
			} else {
				searchParams.delete(name)
			}
		}

		const newUrl = window.location.toString().replace(window.location.search, `?${searchParams.toString()}`)
		window.history.replaceState({}, document.title, newUrl)
	}
}

const normaliseQueryArg = (value: unknown): string | undefined => {
	switch (true) {
		case value === undefined || null === value:
			return undefined

		case 'boolean' === typeof value:
			return value ? '1' : '0'

		case 'number' === typeof value:
		case 'string' === typeof value:
			return String(value)

		default:
			throw new Error(`Unsupported query arg type: ${typeof value}`)
	}
}

export const buildAdminUrl = (
	queryArgs: UrlQueryArgs,
	preserveQueryArgs: string[] = []
) => {
	const searchParams = new URLSearchParams(window.location.search)

	for (const queryArgName of ['page', ...preserveQueryArgs]) {
		const value = searchParams.get(queryArgName)
		if (value && queryArgs[queryArgName] === undefined) {
			queryArgs[queryArgName] = value
		}
	}

	return buildUrl(
		`${window.location.origin}${window.location.pathname}`,
		{ page: searchParams.get('page'), ...queryArgs }
	)
}

export const buildUrl = (
	base: string | undefined,
	queryArgs: UrlQueryArgs
): string => {
	const processedArgs: Record<string, string> = {}

	for (const [key, value] of Object.entries(queryArgs)) {
		const processedArg = normaliseQueryArg(value)

		if (processedArg !== undefined) {
			processedArgs[key] = processedArg
		}
	}

	return addQueryArgs(base, processedArgs)
}
