(() => {
	const options_wrap = document.querySelector('.html-shortcode-options');
	if (!options_wrap) return;

	const options = options_wrap.getElementsByTagName('input');
	const network_admin = -1 !== document.body.className.indexOf('network-admin');

	const snippet_id_input = document.querySelector('input[name=snippet_id]') as HTMLInputElement;
	const snippet_id = snippet_id_input ? parseInt(snippet_id_input.value, 10) : 0;

	const update_shortcode = () => {
		let shortcode = '[code_snippet';

		if (snippet_id) {
			shortcode += ` id=${snippet_id}`;
		}

		if (network_admin) {
			shortcode += ' network=true';
		}

		for (const option of options) {
			if (option.checked) {
				shortcode += ` ${option.value}=true`;
			}
		}

		shortcode += ']';

		document.querySelector('.html-scopes-list').querySelector('.shortcode-tag').textContent = shortcode;
	};

	for (const option of options) {
		option.addEventListener('change', update_shortcode);
	}
})();
