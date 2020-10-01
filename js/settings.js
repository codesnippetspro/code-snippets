/* global code_snippets_editor_atts, code_snippets_editor_settings */

window.addEventListener('DOMContentLoaded', () => {
	'use strict';
	const CodeMirror = window.Code_Snippets_CodeMirror;
	const editor_atts = code_snippets_editor_atts;
	const editor_settings = code_snippets_editor_settings;

	/** @namespace editor_atts.indentWithTabs */
	const n_tabs = editor_atts.indentWithTabs ? Math.floor(editor_atts.indentUnit / editor_atts.tabSize) : 0;
	const n_spaces = editor_atts.indentWithTabs ? editor_atts.indentUnit % editor_atts.tabSize : editor_atts.indentUnit;

	const indent = '\t'.repeat(n_tabs) + ' '.repeat(n_spaces);

	editor_atts['value'] = [
		'add_filter( \'admin_footer_text\', function ( $text ) {\n',
		indent + '$site_name = get_bloginfo( \'name\' );\n',
		indent + '$text = "Thank you for visiting $site_name.";\n',
		indent + 'return $text;',
		'} );\n',
	].join('\n');

	const editor = CodeMirror(document.getElementById('code_snippets_editor_preview'), editor_atts);
	window.code_snippets_editor_preview = editor;

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
});
