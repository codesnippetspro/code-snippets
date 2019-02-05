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


(function () {
	const tabs_wrapper = document.getElementById('snippet-type-tabs');
	if (!tabs_wrapper) return;

	const snippet_form = document.getElementById('snippet-form');

	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');

	const modes = {
		css: 'text/css',
		js: 'javascript',
		php: 'text/x-php',
		html: 'application/x-httpd-php'
	};

	for (let i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener('click', function (e) {
			if (this.classList.contains('nav-tab-active')) return;
			const type = this.getAttribute('data-type');
			e.preventDefault();

			// update the form styles to match the new type
			snippet_form.setAttribute('data-snippet-type', type);

			// switch the active nav tab
			const prev_active = tabs_wrapper.querySelector('.nav-tab-active');
			prev_active.setAttribute('href', '#');
			prev_active.classList.remove('nav-tab-active');

			this.classList.add('nav-tab-active');
			this.removeAttribute('href');

			// select the appropriate scope
			let scope = snippet_form.querySelector(`.${type}-scopes-list input:first-child`);
			if (scope) scope.checked = true;

			// clear the editor contents
			code_snippets_editor.setValue('');
			if (modes[type]) code_snippets_editor.setOption('mode', modes[type]);
		})
	}

})();
