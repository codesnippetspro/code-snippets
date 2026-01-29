import { addQueryArgs } from '@wordpress/url'

export const fetchQueryParam = (name: string): string | undefined => {
	const urlParams = new URLSearchParams(window.location.search)
	return urlParams.get(name) ?? undefined
}

export const updateQueryParam = (name: string, value?: string | number) => {
	if ('URLSearchParams' in window) {
		const searchParams = new URLSearchParams(window.location.search)

		if (value) {
			searchParams.set(name, String(value))
		} else {
			searchParams.delete(name)
		}

		const newUrl = window.location.toString().replace(window.location.search, `?${searchParams.toString()}`)
		window.history.replaceState({}, document.title, newUrl)
	}
}

const normaliseQueryArg = (value: unknown): string | undefined => {
	switch (true) {
		case value === undefined || value === null:
			return undefined

		case typeof value === 'boolean':
			return value ? '1' : '0'

		default:
			return String(value)
	}
}

export const buildUrl = <K extends PropertyKey, V>(
	base: string | undefined,
	queryArgs: { [P in K]?: V }
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
