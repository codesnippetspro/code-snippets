declare var tinymce: any;

type SourceShortcodeOps = {
	id: string
	line_numbers: boolean
};

type ContentShortcodeOps = {
	id: string
	php: boolean
	format: boolean
	shortcodes: boolean
};

(function () {
	tinymce.PluginManager.add('code_snippets', (editor: any) => {
		const ed = tinymce.activeEditor;

		function array_as_values(array: string[]) {
			return Object.keys(array).map(key => ({text: array[Number(key)], value: key}));
		}

		let menu = [
			{
				text: ed.getLang('code_snippets.insert_content_menu'),
				onclick: () => {
					editor.windowManager.open({
						title: ed.getLang('code_snippets.insert_content_title'),
						body: [
							{
								type: 'listbox',
								name: 'id',
								label: ed.getLang('code_snippets.snippet_label'),
								values: array_as_values(ed.getLang('code_snippets.content_snippets')),
							},
							{
								type: 'checkbox',
								name: 'php',
								label: ed.getLang('code_snippets.php_att_label'),
							},
							{
								type: 'checkbox',
								name: 'format',
								label: ed.getLang('code_snippets.format_att_label'),
							},
							{
								type: 'checkbox',
								name: 'shortcodes',
								label: ed.getLang('code_snippets.shortcodes_att_label'),
							}
						],
						onsubmit: (e: { data: ContentShortcodeOps }) => {
							const id = parseInt(e.data.id);
							if (!id) return;

							let shortcode = '[code_snippet id=' + id;

							for (const [opt, val] of Object.entries(e.data)) {
								if ('id' === opt) continue;

								if (val) {
									shortcode += ` ${opt}=${val}`;
								}
							}

							editor.insertContent(shortcode + ']\n');
						}
					});
				}
			},
			{
				text: ed.getLang('code_snippets.insert_source_menu'),
				onclick: () => {
					editor.windowManager.open({
						title: ed.getLang('code_snippets.insert_source_title'),
						body: [
							{
								type: 'listbox',
								name: 'id',
								label: ed.getLang('code_snippets.snippet_label'),
								values: array_as_values(ed.getLang('code_snippets.all_snippets')),
							},
							{
								type: 'checkbox',
								name: 'line_numbers',
								label: ed.getLang('code_snippets.show_line_numbers_label'),
							}
						],
						onsubmit: (e: { data: SourceShortcodeOps }) => {
							const id = parseInt(e.data.id);
							if (!id) return;

							let shortcode = '[code_snippet_source id=' + id;

							if (e.data.line_numbers) {
								shortcode += ' line_numbers=true';
							}

							editor.insertContent(shortcode + ']');
						}
					});
				}
			}
		];

		editor.addButton('code_snippets', {
			icon: 'code',
			menu: menu,
			type: 'menubutton'
		});
	});
})();
