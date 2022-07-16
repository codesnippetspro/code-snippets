const updateShortcode = (options: HTMLCollectionOf<HTMLInputElement>) => {
	const isNetworkAdmin = -1 !== document.body.className.indexOf('network-admin')

	const snippetIdInput = document.querySelector('input[name=snippet_id]') as HTMLInputElement
	const snippetId = snippetIdInput ? parseInt(snippetIdInput.value, 10) : 0

	let shortcode = '[code_snippet'

	if (snippetId) {
		shortcode += ` id=${snippetId}`
	}

	if (isNetworkAdmin) {
		shortcode += ' network=true'
	}

	for (const option of options) {
		if (option.checked) {
			shortcode += ` ${option.value}=true`
		}
	}

	shortcode += ']'

	const scopes = document.querySelector('.html-scopes-list')
	if (scopes) {
		const shortcodeScope = scopes.querySelector('.shortcode-tag')
		if (shortcodeScope) {
			shortcodeScope.textContent = shortcode
		}
	}
}

export const handleContentShortcodeOptions = () => {
	const optionsContainer = document.querySelector('.html-shortcode-options')
	const options = optionsContainer?.getElementsByTagName('input')

	if (options) {
		for (const option of options) {
			option.addEventListener('change', () => updateShortcode(options))
		}
	}
}
