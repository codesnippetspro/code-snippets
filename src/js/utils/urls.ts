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

const sanitizeQueryArg = (value: unknown): string => {
	if (typeof value === 'boolean') {
		return value ? '1' : '0'
	}

	return String(value)
}

export const buildUrl = <K extends PropertyKey, V>(
	base: string | undefined = '',
	queryArgs: { [P in K]?: V }
): string =>
	`${base}?` + Object.entries(queryArgs)
		.filter(([, value]) => value !== undefined && value !== null)
		.map(([queryArg, value]) => `${queryArg}=${encodeURIComponent(sanitizeQueryArg(value))}`)
		.join('&')
