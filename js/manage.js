/* global ajaxurl, pagenow */
'use strict';
(function () {

	const nonce = document.getElementById('code_snippets_ajax_nonce').value;
	const network_admin = ('-network' === pagenow.substring(pagenow.length - '-network'.length));

	function foreach(elements, callback) {
		for (let i = 0; i < elements.length; i++) {
			callback(elements[i], i);
		}
	}

	function update_snippet(field, row_element, snippet, success_callback) {
		var id_column = row_element.querySelector('.column-id');

		if (! id_column || ! parseInt(id_column.textContent)) {
			return;
		}

		snippet['id'] = parseInt(id_column.textContent);
		snippet['shared_network'] = !!row_element.className.match(/\bshared-network-snippet\b/);
		snippet['network'] = snippet['shared_network'] || network_admin;

		var query_string = 'action=update_code_snippet&_ajax_nonce=' + nonce + '&field=' + field + '&snippet=' + JSON.stringify(snippet);

		var request = new XMLHttpRequest();
		request.open('POST', ajaxurl, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

		request.onload = function () {
			if (request.status < 200 || request.status >= 400) return;
			console.log(request.responseText);

			if (success_callback !== undefined) {
				success_callback(request);
			}
		};

		request.send(query_string);
	}

	/* snippet priorities */

	function update_snippet_priority() {
		var row = this.parentElement.parentElement;
		var snippet = {'priority': this.value};
		update_snippet('priority', row, snippet);
	}

	foreach(document.getElementsByClassName('snippet-priority'), function (field, i) {
		field.addEventListener('input', update_snippet_priority);
		field.disabled = false;
	});

	/* activate/deactivate links */

	 function update_view_count(view_count, increment) {
		var n = parseInt(view_count.textContent.replace(/\((\d+)\)/, '$1') );
		increment ? n++ : n--;
		view_count.textContent = '(' + n.toString() + ')';
	}

	function toggle_snippet_active(e) {

		var row = this.parentElement.parentElement; // switch < cell < row
		var match = row.className.match(/\b(?:in)?active-snippet\b/);
		if (! match) return;

		e.preventDefault();

		var activating = 'inactive-snippet' === match[0];
		var snippet = {
			'active': activating
		};

		update_snippet('active', row, snippet, function (request) {

			row.className = (activating) ?
				row.className.replace(/\binactive-snippet\b/, 'active-snippet') :
				row.className.replace(/\bactive-snippet\b/, 'inactive-snippet');

			var views = document.querySelector('.subsubsub');
			update_view_count(views.querySelector('.active .count'), activating);
			update_view_count(views.querySelector('.inactive .count'), activating);
		});
	}

	foreach(document.getElementsByClassName('snippet-activation-switch'), function (link, i) {
		link.addEventListener('click', toggle_snippet_active);
	});

})();
