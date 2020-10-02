/* global code_snippets_editor_settings */
import '../editor-lib';

(function (codeEditor, editor_settings) {
	'use strict';

	window.code_snippets_editor_preview = codeEditor.initialize(document.getElementById('code_snippets_editor_preview'));
	const editor = window.code_snippets_editor_preview.codemirror;

	for (const setting of editor_settings) {
		const element = document.querySelector('[name="code_snippets_settings[editor][' + setting.name + ']"]');

		element.addEventListener('change', () => {
			const opt = setting['codemirror'];

			let value = (() => {
				switch (setting.type) {
					case 'select':
						return element.options[element.selectedIndex].value;
					case 'checkbox':
						return element.checked;
					case 'number':
						return parseInt(element.value);
					default:
						return null;
				}
			})();

			if (null !== value) {
				editor.setOption(opt, value);
			}
		});
	}

}(wp.codeEditor, code_snippets_editor_settings));
