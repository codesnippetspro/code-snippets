import CodeMirror from 'codemirror'
import {diff_match_patch, DIFF_EQUAL,DIFF_DELETE, DIFF_INSERT }from 'diff-match-patch'
import 'codemirror-colorpicker'
import 'codemirror/addon/merge/merge'
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
	window.diff_match_patch = diff_match_patch
	window.DIFF_EQUAL = DIFF_EQUAL
	window.DIFF_DELETE = DIFF_DELETE
	window.DIFF_INSERT = DIFF_INSERT

	const updatedCode = document.getElementById('updated_snippet_code') as HTMLTextAreaElement | null

	if ( updatedCode ) {
		const update_block = document.getElementById('updated-code') as HTMLDivElement | null
		const dv = CodeMirror.MergeView(update_block, {
			value: editor.codemirror.getValue(), 
			origLeft: null,
			orig: updatedCode.value,
			lineNumbers: true,
			theme: 'solarized light',
			highlightDifferences: true,
			connect: 'align',
			mode: editor.codemirror.getOption('mode'),
		})
		//Set height of the editor to be auto and min height to be 350px
		dv.wrap.style.height = 'auto'
		dv.wrap.style.minHeight = '350px'
	}
	
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
