/* global code_snippets_editor_atts */

window.code_snippets_editor = (function (init_editor, editor_atts) {
	const save_snippet_cb = (cm) => document.getElementById('save_snippet').click();

	editor_atts['extraKeys'] = window.navigator.platform.match('Mac') ?
		{'Cmd-Enter': save_snippet_cb, 'Cmd-S': save_snippet_cb} :
		{'Ctrl-Enter': save_snippet_cb, 'Ctrl-S': save_snippet_cb};

	if (window.navigator.platform.match('Mac')) {
		document.querySelector('.editor-help-text').className += ' platform-mac';
	}

	return init_editor(document.getElementById('snippet_code'), editor_atts);

})(window.init_code_snippet_editor, code_snippets_editor_atts);


(function () {
	const tabs_wrapper = document.getElementById('snippet-type-tabs');
	if (!tabs_wrapper) return;

	const editor = code_snippets_editor;
	const snippet_form = document.getElementById('snippet-form');

	const tabs = tabs_wrapper.querySelectorAll('.nav-tab');

	const modes = {
		css: 'text/css',
		js: 'javascript',
		php: 'text/x-php',
		html: 'application/x-httpd-php'
	};

	for (let i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener('click', function (e) {
			if (this.classList.contains('nav-tab-active')) return;
			const type = this.getAttribute('data-type');
			e.preventDefault();

			// update the form styles to match the new type
			snippet_form.setAttribute('data-snippet-type', type);

			// switch the active nav tab
			const prev_active = tabs_wrapper.querySelector('.nav-tab-active');
			prev_active.setAttribute('href', '#');
			prev_active.classList.remove('nav-tab-active');

			this.classList.add('nav-tab-active');
			this.removeAttribute('href');

			// select the appropriate scope
			let scope = snippet_form.querySelector(`.${type}-scopes-list input:first-child`);
			if (scope) scope.checked = true;

			// clear the editor contents
			editor.setValue('');
			editor.setOption('lint', 'js' !== type);
			if (modes[type]) code_snippets_editor.setOption('mode', modes[type]);
		})
	}

})();


(function () {
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
