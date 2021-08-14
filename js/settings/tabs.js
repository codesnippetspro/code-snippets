(function () {
	'use strict';

	const page = document.querySelector('.wrap');
	const tabs_wrapper = document.getElementById('settings-sections-tabs');
	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');
	const http_referer = document.querySelector('input[name=_wp_http_referer]');

	/**
	 * Respond to a user selecting a new settings tab.
	 * @param {Element} tab
	 */
	const select_tab = (tab) => {
		// swap the active tab class from the previously active tab to the current one.
		const active_tab = tabs_wrapper.querySelector('.nav-tab-active');
		if (active_tab) active_tab.classList.remove('nav-tab-active');
		tab.classList.add('nav-tab-active');

		// update the current active tab attribute so that only the active tab is displayed.
		const section = tab.getAttribute('data-section');
		page.setAttribute('data-active-tab', section);

		// refresh the editor preview if we're viewing the editor section.
		if ('editor' === section) {
			const editor = window.code_snippets_editor_preview;
			if (editor && editor.codemirror) editor.codemirror.refresh();
		}

		// update the http referer value so that any redirections lead back to this tab.
		let new_referer = http_referer.value.replace(/([&?]section=)[^&]+/, '$1' + section);
		if (new_referer === http_referer.value) {
			new_referer += '&section=' + section;
		}
		http_referer.value = new_referer;
	};

	// loop through all tabs and add a click event listener.
	for (let i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener('click', (e) => {
			e.preventDefault();
			select_tab(tabs[i]);
		});
	}
})();
