/* global code_snippets_editor_atts */

window.code_snippets_editor = (function (CodeMirror, editor_atts) {
	const snippet_form = document.getElementById('snippet-form');

	const save_snippet_cb = (cm) => document.getElementById('save_snippet').click();

	editor_atts['extraKeys'] = window.navigator.platform.match('Mac') ?
		{'Cmd-Enter': save_snippet_cb, 'Cmd-S': save_snippet_cb} :
		{'Ctrl-Enter': save_snippet_cb, 'Ctrl-S': save_snippet_cb};

	if (window.navigator.platform.match('Mac')) {
		document.querySelector('.editor-help-text').className += ' platform-mac';
	}

	const editor = CodeMirror.fromTextArea(document.getElementById('snippet_code'), editor_atts);

	// set the cursor to the previous position
	let matches = window.location.href.match(/[?&]cursor_line=(\d+)&cursor_ch=(\d+)/);
	if (matches) {
		editor.focus();
		editor.setCursor({line: matches[1], ch: matches[2]});
	}

	// send the current cursor position to the next page
	snippet_form.addEventListener('submit', () => {
		const cursor = editor.getCursor();
		snippet_form.insertAdjacentHTML('beforeend', `<input type="hidden" name="snippet_editor_cursor_line" value="${cursor.line}">`);
		snippet_form.insertAdjacentHTML('beforeend', `<input type="hidden" name="snippet_editor_cursor_ch" value="${cursor.ch}">`);
	});

	return editor;
})(window.Code_Snippets_CodeMirror, code_snippets_editor_atts);
