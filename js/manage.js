'use strict';
(function () {

	var nonce = document.getElementById('code_snippets_ajax_nonce').value;

	var update_snippet_priority = function () {
		var column_id = this.parentElement.parentElement.querySelector('.column-id');

		if (! column_id) {
			return;
		}

		var query_string =
			'action=update_code_snippet_priority&_ajax_nonce=' + nonce +
			'&snippet_id=' + column_id.textContent +
			'&snippet_priority=' + this.value +
			'&snippet_network=' + ('-network' === pagenow.substring(pagenow.length - '-network'.length));

		var request = new XMLHttpRequest();
		request.open('POST', ajaxurl, true);

		request.onload = function() {
			console.log(request.responseText);
		};

		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.send(query_string);
	};

	var priority_fields = document.getElementsByClassName('snippet-priority');

	for (var i = 0; i < priority_fields.length; i++) {
		priority_fields[i].addEventListener('input', update_snippet_priority);
	}

})();
