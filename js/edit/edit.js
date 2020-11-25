'use strict';
import './editor';
import './tabs';
import './shortcode';

(function (strings) {
	document.addEventListener('DOMContentLoaded', () => {
		const form = document.getElementById('snippet-form');
		const editor = window.code_snippets_editor.codemirror;
		const snippet_name = document.querySelector('input[name=snippet_name]');

		if (!form || !editor || !snippet_name) return;

		form.addEventListener('submit', (event) => {
			let message = '';
			const missing_title = '' === snippet_name.value.trim();
			const missing_code = '' === editor.getValue().trim();

			message = missing_title ?
				(missing_code ? strings['missing_title_code'] : strings['missing_title']) :
				(missing_code ? strings['missing_code'] : '');

			if (event['submitter']['id'].startsWith('save_snippet') && message && !confirm(message)) {
				event.preventDefault();
			}
		});
	});
}(window['code_snippets_edit_i18n']));
