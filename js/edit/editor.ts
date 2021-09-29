'use strict';
import '../editor-lib';

window.code_snippets_editor = (codeEditor => {
	const save_snippet_cb = () => document.getElementById('save_snippet').click();

	const atts = {
		viewportMargin: Infinity,
		extraKeys: window.navigator.platform.match('Mac') ?
			{'Cmd-Enter': save_snippet_cb, 'Cmd-S': save_snippet_cb} :
			{'Ctrl-Enter': save_snippet_cb, 'Ctrl-S': save_snippet_cb}
	};

	if (window.navigator.platform.match('Mac')) {
		document.querySelector('.editor-help-text').className += ' platform-mac';
	}

	return codeEditor.initialize(document.getElementById('snippet_code'), atts);
})(window.wp.codeEditor);


(function () {
	const dir_control = document.getElementById('snippet-code-direction') as HTMLSelectElement;
	if (!dir_control) return;

	dir_control.addEventListener('change', () => {
		window.code_snippets_editor.codemirror.setOption('direction', 'rtl' === dir_control.value ? 'rtl' : 'ltr');
	});

})();
