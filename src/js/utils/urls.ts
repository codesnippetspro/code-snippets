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
		console.log(window.location.search, searchParams.toString(), newUrl)
		window.history.replaceState({}, document.title, newUrl)
	}
}
