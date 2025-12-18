import tinymce from 'tinymce'
import type { Editor } from 'tinymce'
import type { ContentShortcodeAtts, SourceShortcodeAtts } from './types/Shortcodes'
import type { LocalisedEditor } from './types/WordPressEditor'

const convertToValues = (array: Record<string, string>) =>
	Object.keys(array).map(key => ({
		text: array[Number(key)],
		value: key
	}))

export const insertContentMenu = (editor: Editor, activeEditor: LocalisedEditor) => ({
	text: activeEditor.getLang('code_snippets.insert_source_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: activeEditor.getLang('code_snippets.insert_source_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: activeEditor.getLang('code_snippets.snippet_label'),
					values: convertToValues(<Record<string, string>> activeEditor.getLang('code_snippets.all_snippets'))
				},
				{
					type: 'checkbox',
					name: 'line_numbers',
					label: activeEditor.getLang('code_snippets.show_line_numbers_label')
				}
			],
			onsubmit: (event: { data: SourceShortcodeAtts }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) {
					return
				}

				let atts = ''

				if (event.data.line_numbers) {
					atts += ' line_numbers=true'
				}

				editor.insertContent(`[code_snippet_source id=${id}${atts}]`)
			}
		}, {})
	}
})

export const insertSourceMenu = (editor: Editor, ed: LocalisedEditor) => ({
	text: ed.getLang('code_snippets.insert_content_menu'),
	onclick: () => {
		editor.windowManager.open({
			title: ed.getLang('code_snippets.insert_content_title'),
			body: [
				{
					type: 'listbox',
					name: 'id',
					label: ed.getLang('code_snippets.snippet_label'),
					values: convertToValues(<Record<string, string>> ed.getLang('code_snippets.content_snippets'))
				},
				{
					type: 'checkbox',
					name: 'php',
					label: ed.getLang('code_snippets.php_att_label')
				},
				{
					type: 'checkbox',
					name: 'format',
					label: ed.getLang('code_snippets.format_att_label')
				},
				{
					type: 'checkbox',
					name: 'shortcodes',
					label: ed.getLang('code_snippets.shortcodes_att_label')
				}
			],
			onsubmit: (event: { data: ContentShortcodeAtts }) => {
				const id = parseInt(event.data.id, 10)
				if (!id) {
					return
				}

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

// Custom scissors icon as base64-encoded SVG (same as used in WP admin menu)
// Base64-encoded version of menu-icon.svg
const scissorsIcon =
	'data:image/svg+xml;base64,' +
	'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZX' + 
  'dCb3g9IjAgMCAyNiAyNSI+PHBhdGggZmlsbD0iIzUxNTc1ZiIgZD0iTTYuMTI3IDExLjk2Nmgz' + 
  'LjQxNGEuOTEuOTEgMCAwIDEgLjg0NC41NjMuOTMuOTMgMCAwIDEtLjE4NSAxLjAwNGwuMDA1Lj' + 
  'AxLTIuMzM4IDIuMzU1YTQuMTkgNC4xOSAwIDAgMCAwIDUuODg1QTQuMTEgNC4xMSAwIDAgMCAx' + 
  'MC43ODQgMjNhNC4xMSA0LjExIDAgMCAwIDIuOTE3LTEuMjE3IDQuMiA0LjIgMCAwIDAgMS4wND' + 
  'gtMS44MDIgNC4yIDQuMiAwIDAgMC0uOTE1LTMuOTQgNC4xIDQuMSAwIDAgMC0xLjczMi0xLjE0' + 
  'NWwuNjE0LS42MTloNy44NDZjMS44NyAwIDMuMzkxLTEuNjA0IDMuNDM2LTMuNjA2IDAtLjAzMy' + 
  '4wMDQtLjA2IDAtLjA5MmExLjAyIDEuMDIgMCAwIDAtLjMyNi0uNjYgMSAxIDAgMCAwLS42ODEt' + 
  'LjI2NmgtNS42OTJsNC4xMS00LjE0NWExLjAyNSAxLjAyNSAwIDAgMCAuMDY4LTEuMzc0Yy0uMD' + 
  'IyLS4wMjctLjA0NC0uMDQ2LS4wNjgtLjA2OC0xLjQzLTEuMzc4LTMuNjM0LTEuNDMtNC45NTMt' + 
  'LjA5OGwtNS42MzUgNS42ODVIOS44MmMuMzk4LS44MS41MjQtMS43My4zNTgtMi42MTlhNC4xNy' + 
  'A0LjE3IDAgMCAwLTEuMjc5LTIuMzA4IDQuMDk2IDQuMDk2IDAgMCAwLTQuOTUtLjQ1NyA0LjE1' + 
  'IDQuMTUgMCAwIDAtMS42NzIgMi4wMzYgNC4yIDQuMiAwIDAgMC0uMTE5IDIuNjQyYy4yNDYuOD' + 
  'cuNzY3IDEuNjM1IDEuNDgzIDIuMThzMS41OS44NCAyLjQ4Ni44MzlNNy45NiA3LjgwNWMwIC40' + 
  'OS0uMTkzLjk2LS41MzYgMS4zMDhhMS44MjUgMS44MjUgMCAwIDEtMi41OTIuMDAxQTEuODU3ID' + 
  'EuODU3IDAgMCAxIDQuODMgNi41YTEuODI1IDEuODI1IDAgMCAxIDIuNTkzIDBjLjM0My4zNDYu' + 
  'NTM3LjgxNi41MzcgMS4zMDZtNC4xMTkgOS43MzNhMS44NiAxLjg2IDAgMCAxLS41OTUgMy4wMT' + 
  'QgMS44MSAxLjgxIDAgMCAxLTEuOTk5LS40MDIgMS44NSAxLjg1IDAgMCAxLS41MzYtMS4zMDYg' + 
  'MS44NiAxLjg2IDAgMCAxIDEuMTMtMS43MSAxLjgxIDEuODEgMCAwIDEgMiAuNDA0Ii8+PC9zdmc+'

tinymce.PluginManager.add('code_snippets', editor => {
	const activeEditor = <LocalisedEditor> tinymce.activeEditor

	editor.addButton('code_snippets', {
		type: 'menubutton',
		title: 'Code Snippets',
		image: scissorsIcon,
		menu: [
			insertContentMenu(editor, activeEditor), 
			insertSourceMenu(editor, activeEditor)
		],
	})
})
