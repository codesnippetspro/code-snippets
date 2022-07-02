(() => {
	const optionsContainer = document.querySelector('.html-shortcode-options');
	if (!optionsContainer) return;

	const options = optionsContainer.getElementsByTagName('input');
	const isNetworkAdmin = -1 !== document.body.className.indexOf('network-admin');

	const snippetIdInput = document.querySelector('input[name=snippet_id]') as HTMLInputElement;
	const snippetId = snippetIdInput ? parseInt(snippetIdInput.value, 10) : 0;

	const updateShortcode = () => {
		let shortcode = '[code_snippet';

		if (snippetId) {
			shortcode += ` id=${snippetId}`;
		}

		if (isNetworkAdmin) {
			shortcode += ' network=true';
		}

		for (const option of options) {
			if (option.checked) {
				shortcode += ` ${option.value}=true`;
			}
		}

		shortcode += ']';

		const scopes = document.querySelector('.html-scopes-list');
		if (scopes) {
			const shortcodeScope = scopes.querySelector('.shortcode-tag');
			if (shortcodeScope) {
				shortcodeScope.textContent = shortcode
			}
		}
	};

	for (const option of options) {
		option.addEventListener('change', updateShortcode);
	}
})();
