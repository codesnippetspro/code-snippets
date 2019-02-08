(function () {
	tinymce.PluginManager.add('code_snippets', editor => {
		const ed = tinymce.activeEditor;

		let menu = [
			{
				text: ed.getLang('code_snippets.insert_content_menu'),
				onclick: () => {
					editor.windowManager.open({
						title: ed.getLang('code_snippets.insert_content_title'),
						body: [
							{
								type: 'textbox',
								name: 'id',
								label: ed.getLang('code_snippets.snippet_id_label'),
								size: 2,
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
						onsubmit: e => {
							const id = parseInt(e.data.id);
							if (! id) return;

							let shortcode = '[code_snippet id=' + id;

							for (const opt of Object.keys(e.data)) {
								if ('id' === opt) continue;
								let val = e.data[opt];

								if (val) {
									shortcode += ` ${opt}=${val}`;
								}
							}

							editor.insertContent(shortcode + ']');
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
								type: 'textbox',
								name: 'id',
								label: ed.getLang('code_snippets.snippet_id_label'),
								size: 2,
							},
							{
								type: 'checkbox',
								name: 'line_numbers',
								label: ed.getLang('code_snippets.show_line_numbers_label'),
							}
						],
						onsubmit: e => {
							const id = parseInt(e.data.id);
							if (! id) return;

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
