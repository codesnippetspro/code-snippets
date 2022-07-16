import '../editor'

const parseSelect = (select: HTMLSelectElement) => select.options[select.selectedIndex].value
const parseCheckbox = (checkbox: HTMLInputElement) => checkbox.checked
const parseNumber = (input: HTMLInputElement) => parseInt(input.value, 10)

const initialiseCodeMirror = () => {
	const { codeEditor } = window.wp
	const textarea = document.getElementById('code_snippets_editor_preview')

	if (textarea) {
		window.code_snippets_editor_preview = codeEditor.initialize(textarea)
		return window.code_snippets_editor_preview.codemirror
	}

	console.error('Could not initialise CodeMirror on textarea.', textarea)
}

export const handleEditorPreviewUpdates = () => {
	const editor = initialiseCodeMirror()
	const editorSettings = window.code_snippets_editor_settings

	for (const setting of editorSettings) {
		const element = document.querySelector(`[name="code_snippets_settings[editor][${setting.name}]"]`)

		element?.addEventListener('change', () => {
			const opt = setting.codemirror

			const value = (() => {
				switch (setting.type) {
					case 'select':
						return parseSelect(element as HTMLSelectElement)
					case 'checkbox':
						return parseCheckbox(element as HTMLInputElement)
					case 'number':
						return parseNumber(element as HTMLInputElement)
					default:
						return null
				}
			})()

			if (null !== value) {
				editor?.setOption(opt, value)
			}
		})
	}
}
