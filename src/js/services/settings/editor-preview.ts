import '../../editor'

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
	return undefined
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
						return parseSelect(<HTMLSelectElement> element)
					case 'checkbox':
						return parseCheckbox(<HTMLInputElement> element)
					case 'number':
						return parseNumber(<HTMLInputElement> element)
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
