import '../editor-lib';

window.code_snippets_editor = (({codeEditor}) => {
	const editor = codeEditor.initialize(document.getElementById('snippet_code'));

	const controlKey = window.navigator.platform.match('Mac') ? 'Cmd' : 'Ctrl';
	const save_snippet_cb = () => document.getElementById('save_snippet').click();

	editor.codemirror.setOption('extraKeys', {
		[`${controlKey}-S`]: save_snippet_cb,
		[`${controlKey}-Enter`]: save_snippet_cb,
	});

	return editor;
})(window.wp);

if (window.navigator.platform.match('Mac')) {
	document.querySelector('.editor-help-text').className += ' platform-mac';
}

const dir_control = document.getElementById('snippet-code-direction') as HTMLSelectElement;

if (dir_control) {
	dir_control.addEventListener('change', () => {
		window.code_snippets_editor.codemirror.setOption('direction', 'rtl' === dir_control.value ? 'rtl' : 'ltr');
	});
}
