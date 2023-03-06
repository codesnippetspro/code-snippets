import * as tinymce from 'tinymce'
import { Editor } from 'tinymce'

interface SourceShortcodeOps {
	id: string
	line_numbers: boolean
}

interface ContentShortcodeOps {
	id: string
	php: boolean
	format: boolean
	shortcodes: boolean
}

interface WordPressEditor extends Editor {
	getLang: (s: string) => string | Record<string, string>
}

const convertToValues = (array: Record<string, string>) =>
	Object.keys(array).map(key => ({
		text: array[Number(key)],
		value: key
	}))

const insertContentMenu = (editor: Editor, activeEditor: WordPressEditor) => ({
	text: activeEditor.getLang('code_snippets.insert_source_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: activeEditor.getLang('code_snippets.insert_source_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: activeEditor.getLang('code_snippets.snippet_label'),
					values: convertToValues(activeEditor.getLang('code_snippets.all_snippets') as Record<string, string>),
				},
				{
					type: 'checkbox',
					name: 'line_numbers',
					label: activeEditor.getLang('code_snippets.show_line_numbers_label'),
				}
			],
			onsubmit: (event: { data: SourceShortcodeOps }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) return

				let atts = ''

				if (event.data.line_numbers) {
					atts += ' line_numbers=true'
				}

				editor.insertContent(`[code_snippet_source id=${id}${atts}]`)
			}
		}, {})
	}
})

const insertSourceMenu = (editor: Editor, ed: WordPressEditor) => ({
	text: ed.getLang('code_snippets.insert_content_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: ed.getLang('code_snippets.insert_content_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: ed.getLang('code_snippets.snippet_label'),
					values: convertToValues(ed.getLang('code_snippets.content_snippets') as Record<string, string>),
				},
				{
					type: 'checkbox',
					name: 'php',
					label: ed.getLang('code_snippets.php_att_label'),
				},
				{
					type: 'checkbox',
					name: 'format',
					label: ed.getLang('code_snippets.format_att_label'),
				},
				{
					type: 'checkbox',
					name: 'shortcodes',
					label: ed.getLang('code_snippets.shortcodes_att_label'),
				}
			],
			onsubmit: (event: { data: ContentShortcodeOps }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) return

				let atts = ''

				for (const [opt, val] of Object.entries(event.data)) {
					if ('id' !== opt && val) {
						atts += ` ${opt}=${val}`
					}
				}

				editor.insertContent(`[code_snippet id=${id}${atts}]`)
			}
		}, {})
	}
})

tinymce.PluginManager.add('code_snippets', editor => {
	const activeEditor = tinymce.activeEditor as WordPressEditor

	editor.addButton('code_snippets', {
		icon: 'code',
		menu: [insertContentMenu(editor, activeEditor), insertSourceMenu(editor, activeEditor)],
		type: 'menubutton'
	})
})
