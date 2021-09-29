import '../globals';

(function (editor) {
	'use strict';

	const tabs_wrapper = document.getElementById('snippet-type-tabs');
	if (!tabs_wrapper) return;

	const snippet_form = document.getElementById('snippet-form');

	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');

	const modes = {
		css: 'text/css',
		js: 'javascript',
		php: 'text/x-php',
		html: 'application/x-httpd-php'
	} as Record<string, string>;

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
			const scope = snippet_form.querySelector(`.${type}-scopes-list input:first-child`) as HTMLInputElement;
			if (scope) scope.checked = true;

			// clear the editor contents
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			editor.setOption('lint', 'php' === type || 'css' === type);
			if (modes[type]) editor.setOption('mode', modes[type]);
		});
	}

})(window.code_snippets_editor.codemirror);
