'use strict';
import './editor';
import './tabs';
import './shortcode';

(function () {
	document.addEventListener('DOMContentLoaded', () => {
		const form = document.getElementById('snippet-form');
		const editor = window.code_snippets_editor.codemirror;
		const snippet_name = document.querySelector('input[name=snippet_name]');
		const text = form.getAttribute('data-submit-warning');

		if (!form || !editor || !snippet_name) return;

		form.addEventListener('submit', (event) => {
			if ('' === editor.getValue().trim() && '' === snippet_name.value.trim() && !confirm(text)) {
				event.preventDefault();
			}
		});
	});
}());
