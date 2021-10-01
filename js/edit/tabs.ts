import {SnippetType} from '../types';

(editor => {
	const tabs_wrapper = document.getElementById('snippet-type-tabs');
	if (!tabs_wrapper) return;

	const snippet_form = document.getElementById('snippet-form');

	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');

	const modes = {
		css: 'text/css',
		js: 'javascript',
		php: 'text/x-php',
		html: 'application/x-httpd-php'
	} as Record<SnippetType, string>;

	const selectScope = (type: SnippetType) => {
		const scope = snippet_form.querySelector(`.${type}-scopes-list input:first-child`) as HTMLInputElement;
		if (scope) scope.checked = true;

		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		editor.setOption('lint', 'php' === type || 'css' === type);
		if (type in modes) editor.setOption('mode', modes[type]);
	};

	const switchTab = (tab: Element) => {
		const prev_active = tabs_wrapper.querySelector('.nav-tab-active');
		prev_active.setAttribute('href', '#');
		prev_active.classList.remove('nav-tab-active');

		tab.classList.add('nav-tab-active');
		tab.removeAttribute('href');
	};

	for (const tab of tabs) {
		tab.addEventListener('click', event => {
			if (tab.classList.contains('nav-tab-active')) return;
			const type = tab.getAttribute('data-type') as SnippetType;
			event.preventDefault();

			// Update the form styles to match the new type.
			snippet_form.setAttribute('data-snippet-type', type);

			// Switch the active tab and change the snippet scope.
			switchTab(tab);
			selectScope(type);
		});
	}

})(window.code_snippets_editor.codemirror);
