'use strict';
import './globals'

declare const pagenow: string;
declare const ajaxurl: string;

type Snippet = {
	id: number
	name: string
	scope: string
	active: boolean
	network: boolean
	shared_network: boolean
	priority: number
};

type WPJSONResponse = {
	success: boolean
	data?: unknown
}

(function () {
	const nonce = (document.getElementById('code_snippets_ajax_nonce') as HTMLInputElement).value;
	const network_admin = ('-network' === pagenow.substring(pagenow.length - '-network'.length));
	const strings = window.code_snippets_manage_i18n;

	/**
	 * Utility function to loop through a DOM list
	 * @param elements
	 * @param callback
	 */
	function foreach(elements: HTMLCollectionBase, callback: (element: Element, index: number) => void) {
		for (let i = 0; i < elements.length; i++) {
			callback(elements[i], i);
		}
	}

	/**
	 * Update the data of a given snippet using AJAX
	 * @param field
	 * @param row_element
	 * @param snippet
	 * @param success_callback
	 */
	function update_snippet(field: string, row_element: Element, snippet: Snippet, success_callback?: (response: WPJSONResponse) => void) {
		const id_column = row_element.querySelector('.column-id');

		if (!id_column || !parseInt(id_column.textContent)) {
			return;
		}

		snippet['id'] = parseInt(id_column.textContent);
		snippet['shared_network'] = !!row_element.className.match(/\bshared-network-snippet\b/);
		snippet['network'] = snippet['shared_network'] || network_admin;
		snippet['scope'] = row_element.getAttribute('data-snippet-scope');

		const query_string = 'action=update_code_snippet&_ajax_nonce=' + nonce + '&field=' + field + '&snippet=' + JSON.stringify(snippet);

		const request = new XMLHttpRequest();
		request.open('POST', ajaxurl, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

		request.onload = () => {
			if (request.status < 200 || request.status >= 400) return;
			console.log(request.responseText);

			if (success_callback !== undefined) {
				success_callback(JSON.parse(request.responseText));
			}
		};

		request.send(query_string);
	}

	/* snippet priorities */

	/**
	 * Update the priority of a snippet
	 */
	function update_snippet_priority() {
		const row = this.parentElement.parentElement;
		const snippet = {'priority': this.value} as Snippet;
		update_snippet('priority', row, snippet);
	}

	foreach(document.getElementsByClassName('snippet-priority'), (field: HTMLInputElement) => {
		field.addEventListener('input', update_snippet_priority);
		field.disabled = false;
	});

	/* activate/deactivate links */

	/**
	 * Update the snippet count of a specific view
	 * @param view_count
	 * @param increment
	 */
	function update_view_count(view_count: HTMLElement, increment: boolean) {
		let n = parseInt(view_count.textContent.replace(/\((\d+)\)/, '$1'));
		increment ? n++ : n--;
		view_count.textContent = '(' + n.toString() + ')';
	}

	/**
	 * Activate an inactive snippet, or deactivate an active snippet
	 * @param e
	 */
	function toggle_snippet_active(e: Event) {

		const row = this.parentElement.parentElement; // switch < cell < row
		const match = row.className.match(/\b(?:in)?active-snippet\b/);
		if (!match) return;

		e.preventDefault();

		const activating = 'inactive-snippet' === match[0];
		const snippet = {'active': activating} as Snippet;

		update_snippet('active', row, snippet, (response) => {
			const button = row.querySelector('.snippet-activation-switch');

			if (response.success) {
				row.className = (activating) ?
					row.className.replace(/\binactive-snippet\b/, 'active-snippet') :
					row.className.replace(/\bactive-snippet\b/, 'inactive-snippet');

				const views = document.querySelector('.subsubsub');
				update_view_count(views.querySelector('.active .count'), activating);
				update_view_count(views.querySelector('.inactive .count'), activating);

				button.title = activating ? strings['deactivate'] : strings['activate'];
			} else {
				row.className += ' erroneous-snippet';
				button.title = strings['activation_error'];
			}
		});
	}

	foreach(document.getElementsByClassName('snippet-activation-switch'), (link) => {
		link.addEventListener('click', toggle_snippet_active);
	});
})();
