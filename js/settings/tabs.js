(function () {
	'use strict';

	const tabs_wrapper = document.getElementById('settings-sections-tabs');
	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');
	const sections = document.getElementsByClassName('settings-section');
	const http_referer = document.querySelector('input[name=_wp_http_referer]');

	const select_tab = (tab) => {
		const active_tab = tabs_wrapper.querySelector('.nav-tab-active');
		if (active_tab) active_tab.classList.remove('nav-tab-active');
		tab.classList.add('nav-tab-active');
		const section = tab.getAttribute('data-section');

		for (let j = 0; j < sections.length; j++) {
			sections[j].style.display = sections[j].classList.contains(section + '-settings') ? 'block' : 'none';
		}

		if ('editor' === section) {
			const editor = window.code_snippets_editor_preview;
			if (editor && editor.codemirror) editor.codemirror.refresh();
		}

		let new_referer = http_referer.value.replace(/([&?]section=)[^&]+/, '$1' + section);
		if (new_referer === http_referer.value) {
			new_referer += '&section=' + section;
		}
		http_referer.value = new_referer;
	};

	for (let i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener('click', (e) => {
			e.preventDefault();
			select_tab(tabs[i]);
		});
	}
})();
