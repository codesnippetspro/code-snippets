import { __ } from '@wordpress/i18n'
import { getEditorPhrases } from '../../editor/phrases'
import { createSnippetEditor, setEditorSettings } from '../../editor/snippetEditor'
import type { EditorView } from '@codemirror/view'
import type { EditorSettings } from '../../types/EditorSettings'

const parseSelect = (select: HTMLSelectElement) => select.options[select.selectedIndex].value
const parseCheckbox = (checkbox: HTMLInputElement) => checkbox.checked
const parseNumber = (input: HTMLInputElement) => parseInt(input.value, 10)

const initialiseEditor = (settings: EditorSettings): EditorView | undefined => {
	const textarea = document.getElementById('code_snippets_editor_preview')

	if (!(textarea instanceof HTMLTextAreaElement)) {
		console.error('Could not find the code editor preview.', textarea)
		return undefined
	}

	const parent = document.createElement('div')
	textarea.after(parent)
	textarea.hidden = true

	return createSnippetEditor({
		parent,
		doc: textarea.value,
		snippetType: 'php',
		settings,
		surface: 'settings',
		phrases: getEditorPhrases(),
		contentAttributes: { 'aria-label': __('Code editor preview', 'code-snippets') }
	})
}

export const handleEditorPreviewUpdates = () => {
	const config = window.CODE_SNIPPETS_EDITOR

	if (!config?.enabled) {
		return
	}

	let { settings } = config
	const editor = initialiseEditor(settings)

	for (const setting of window.code_snippets_editor_settings) {
		const element = document.querySelector(`[name="code_snippets_settings[editor][${setting.name}]"]`)

		element?.addEventListener('change', () => {
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

			if (null === value || !editor) {
				return
			}

			if ('fontSize' === setting.codemirror) {
				editor.dom.style.fontSize = `${String(value)}px`
				editor.requestMeasure()
			} else {
				settings = { ...settings, [setting.codemirror]: value }
				setEditorSettings(editor, settings)
			}
		})
	}
}
