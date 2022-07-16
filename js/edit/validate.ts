export const handleFormSubmitValidation = () => {
	const form = document.getElementById('snippet-form')
	const editor = window.code_snippets_editor?.codemirror
	const strings = window.code_snippets_edit_i18n
	const snippetName = document.querySelector('input[name=snippet_name]') as HTMLInputElement

	if (!form || !editor || !snippetName) {
		return
	}

	form.addEventListener('submit', (event: SubmitEvent) => {
		const missing_title = '' === snippetName.value.trim()
		const missing_code = '' === editor.getValue().trim()

		const message = missing_title ?
			missing_code ? strings.missing_title_code : strings.missing_title :
			missing_code ? strings.missing_code : ''

		if (event?.submitter?.id.startsWith('save_snippet') && message && !confirm(message)) {
			event.preventDefault()
		}
	})
}
