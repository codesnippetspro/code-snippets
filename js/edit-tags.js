import 'tag-it/js/tag-it';

/* global code_snippets_all_tags, jQuery */

(function () {
	const tags_field = document.getElementById('snippet_tags');

	if (!tags_field) return;

	try {
		jQuery(tags_field).tagit({
			availableTags: code_snippets_all_tags,
			allowSpaces: true,
			removeConfirmation: true,
			showAutocompleteOnFocus: true,
		});

	} catch (e) {
		console.log('Could not initialise snippet tag field')
	}

})();
