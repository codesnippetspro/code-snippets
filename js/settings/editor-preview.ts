import '../editor-lib';
import {EditorOption} from '../globals';

(function (codeEditor, editor_settings: EditorOption[]) {
	'use strict';

	window.code_snippets_editor_preview = codeEditor.initialize(document.getElementById('code_snippets_editor_preview'));
	const editor = window.code_snippets_editor_preview.codemirror;

	for (const setting of editor_settings) {
		const element = document.querySelector('[name="code_snippets_settings[editor][' + setting.name + ']"]');

		element.addEventListener('change', () => {
			const opt = setting['codemirror'];

			const value = (() => {
				switch (setting.type) {
					case 'select':
						return (element as HTMLSelectElement).options[(element as HTMLSelectElement).selectedIndex].value;
					case 'checkbox':
						return (element as HTMLInputElement).checked;
					case 'number':
						return parseInt((element as HTMLInputElement).value);
					default:
						return null;
				}
			})();

			if (null !== value) {
				editor.setOption(opt, value);
			}
		});
	}

}(window.wp.codeEditor, window.code_snippets_editor_settings));
