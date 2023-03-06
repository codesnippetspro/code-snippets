export const handleFormSubmitValidation = () => {
	const form = document.getElementById('snippet-form')
	const editor = window.code_snippets_editor?.codemirror
	const strings = window.code_snippets_edit_i18n
	const snippetName = document.querySelector('input[name=snippet_name]') as HTMLInputElement

	if (!form || !editor || !snippetName) {
		return
	}

	form.addEventListener('submit', (event: SubmitEvent) => {
		const missingTitle = '' === snippetName.value.trim()
		const missingCode = '' === editor.getValue().trim()

		const message = missingTitle ?
			missingCode ? strings.missing_title_code : strings.missing_title :
			missingCode ? strings.missing_code : ''

		if (event?.submitter?.id.startsWith('save_snippet') && message && !confirm(message)) {
			event.preventDefault()
		}
	})
}
