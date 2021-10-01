import {Snippet} from './types';

type SuccessCallback = (response: {success: boolean, data?: unknown}) => void;

const nonce_input = document.getElementById('code_snippets_ajax_nonce') as HTMLInputElement;
const nonce = nonce_input.value;
const network_admin = '-network' === window.pagenow.substring(window.pagenow.length - '-network'.length);
const strings = window.code_snippets_manage_i18n;

const send_snippet_request = (query: string, success_callback?: SuccessCallback) => {
	const request = new XMLHttpRequest();
	request.open('POST', window.ajaxurl, true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

	request.onload = () => {
		const success = 200;
		const errorStart = 400;
		if (success > request.status || errorStart <= request.status) return;
		// eslint-disable-next-line no-console
		console.log(request.responseText);

		if (success_callback) {
			success_callback(JSON.parse(request.responseText));
		}
	};

	request.send(query);
};

/**
 * Update the data of a given snippet using AJAX
 * @param field
 * @param row_element
 * @param snippet
 * @param success_callback
 */
const update_snippet = (field: string, row_element: Element, snippet: Snippet, success_callback?: SuccessCallback) => {
	const id_column = row_element.querySelector('.column-id');

	if (!id_column || !parseInt(id_column.textContent, 10)) {
		return;
	}

	snippet.id = parseInt(id_column.textContent, 10);
	snippet.shared_network = Boolean(row_element.className.match(/\bshared-network-snippet\b/));
	snippet.network = snippet.shared_network || network_admin;
	snippet.scope = row_element.getAttribute('data-snippet-scope');

	const query_string = `action=update_code_snippet&_ajax_nonce=${nonce}&field=${field}&snippet=${JSON.stringify(snippet)}`;
	send_snippet_request(query_string, success_callback);
};

/* Snippet priorities */

/**
 * Update the priority of a snippet
 */
const update_snippet_priority = (element: HTMLInputElement) => {
	const row = element.parentElement.parentElement;
	const snippet: Snippet = {priority: parseFloat(element.value)};
	update_snippet('priority', row, snippet);
};

for (const field of document.getElementsByClassName('snippet-priority') as HTMLCollectionOf<HTMLInputElement>) {
	field.addEventListener('input', () => update_snippet_priority(field));
	field.disabled = false;
}

/* Activate/deactivate links */

/**
 * Update the snippet count of a specific view
 * @param view_count
 * @param increment
 */
const update_view_count = (view_count: HTMLElement, increment: boolean) => {
	let count = parseInt(view_count.textContent.replace(/\((?<count>\d+)\)/, '$count'), 10);
	count += increment ? 1 : -1;
	view_count.textContent = `(${count.toString()})`;
};

/**
 * Activate an inactive snippet, or deactivate an active snippet
 * @param link
 * @param event
 */
const toggle_snippet_active = (link: HTMLAnchorElement, event: Event) => {

	// Switch < cell < row
	const row = link.parentElement.parentElement;
	const match = row.className.match(/\b(?:in)?active-snippet\b/);
	if (!match) return;

	event.preventDefault();

	const activating = 'inactive-snippet' === match[0];
	const snippet = {active: activating} as Snippet;

	update_snippet('active', row, snippet, response => {
		const button = row.querySelector('.snippet-activation-switch') as HTMLAnchorElement;

		if (response.success) {
			row.className = activating ?
				row.className.replace(/\binactive-snippet\b/, 'active-snippet') :
				row.className.replace(/\bactive-snippet\b/, 'inactive-snippet');

			const views = document.querySelector('.subsubsub');
			update_view_count(views.querySelector('.active .count'), activating);
			update_view_count(views.querySelector('.inactive .count'), activating);

			button.title = activating ? strings.deactivate : strings.activate;
		} else {
			row.className += ' erroneous-snippet';
			button.title = strings.activation_error;
		}
	});
};

for (const link of document.getElementsByClassName('snippet-activation-switch')) {
	link.addEventListener('click', event => toggle_snippet_active(link as HTMLAnchorElement, event));
}
