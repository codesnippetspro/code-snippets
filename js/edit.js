/* global code_snippets_editor_atts */

window.code_snippets_editor = (function (CodeMirror, editor_atts) {
	const save_snippet_cb = (cm) => document.getElementById('save_snippet').click();

	editor_atts['extraKeys'] = window.navigator.platform.match('Mac') ?
		{'Cmd-Enter': save_snippet_cb, 'Cmd-S': save_snippet_cb} :
		{'Ctrl-Enter': save_snippet_cb, 'Ctrl-S': save_snippet_cb};

	if (window.navigator.platform.match('Mac')) {
		document.querySelector('.editor-help-text').className += ' platform-mac';
	}

	return CodeMirror.fromTextArea(document.getElementById('snippet_code'), editor_atts);

})(window.Code_Snippets_CodeMirror, code_snippets_editor_atts);
