(function () {
	'use strict';

	const options_wrap = document.querySelector('.html-shortcode-options');
	if (!options_wrap) return;

	const options = options_wrap.getElementsByTagName('input');
	const network_admin = -1 !== document.body.className.indexOf('network-admin');

	let snippet_id = document.querySelector('input[name=snippet_id]');
	snippet_id = snippet_id ? parseInt(snippet_id.value) : 0;

	const update_shortcode = () => {
		let shortcode = '[code_snippet';

		if (snippet_id) {
			shortcode += 'id=' + snippet_id;
		}

		if (network_admin) {
			shortcode += ' network=true';
		}

		for (let i = 0; i < options.length; i++) {
			if (options[i].checked) {
				shortcode += ' ' + options[i].value + '=true';
			}
		}

		shortcode += ']';

		document.querySelector('.html-scopes-list').querySelector('.shortcode-tag').textContent = shortcode;
	};

	for (let i = 0; i < options.length; i++) {
		options[i].addEventListener('change', update_shortcode);
	}
}());
