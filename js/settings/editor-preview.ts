import '../editor-lib';
import {EditorOption} from '../globals';

((codeEditor, editor_settings: EditorOption[]) => {

	const editor_preview_textarea = document.getElementById('code_snippets_editor_preview');
	window.code_snippets_editor_preview = codeEditor.initialize(editor_preview_textarea);
	const editor = window.code_snippets_editor_preview.codemirror;

	const parseSelect = (select: HTMLSelectElement) => select.options[select.selectedIndex].value;
	const parseCheckbox = (checkbox: HTMLInputElement) => checkbox.checked;
	const parseNumber = (input: HTMLInputElement) => parseInt(input.value, 10);

	for (const setting of editor_settings) {
		const element = document.querySelector(`[name="code_snippets_settings[editor][${setting.name}]"]`);


		element.addEventListener('change', () => {
			const opt = setting.codemirror;

			const value = (() => {
				switch (setting.type) {
					case 'select':
						return parseSelect(element as HTMLSelectElement);
					case 'checkbox':
						return parseCheckbox(element as HTMLInputElement);
					case 'number':
						return parseNumber(element as HTMLInputElement);
					default:
						return null;
				}
			})();

			if (null !== value) {
				editor.setOption(opt, value);
			}
		});
	}

})(window.wp.codeEditor, window.code_snippets_editor_settings);
