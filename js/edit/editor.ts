import '../editor'

export const loadSnippetCodeEditor = () => {
	const { codeEditor } = window.wp

	const textarea = document.getElementById('snippet_code')

	if (!textarea) {
		console.error('Could not initialise CodeMirror on textarea.', textarea)
		return
	}

	const editor = codeEditor.initialize(textarea)
	window.code_snippets_editor = editor

	const extraKeys = editor.codemirror.getOption('extraKeys')
	const controlKey = window.navigator.platform.match('Mac') ? 'Cmd' : 'Ctrl'
	const saveSnippet = () => document.getElementById('save_snippet')?.click()

	editor.codemirror.setOption('extraKeys', {
		...'object' === typeof extraKeys ? extraKeys : {},
		[`${controlKey}-S`]: saveSnippet,
		[`${controlKey}-Enter`]: saveSnippet,
	})

	if (window.navigator.platform.match('Mac')) {
		const helpText = document.querySelector('.editor-help-text')
		if (helpText) {
			helpText.className += ' platform-mac'
		}
	}

	const directionControl = document.getElementById('snippet-code-direction') as HTMLSelectElement | null
	directionControl?.addEventListener('change', () => {
		window.code_snippets_editor?.codemirror.setOption('direction', 'rtl' === directionControl.value ? 'rtl' : 'ltr')
	})
}
