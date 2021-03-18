import tagger from '@jcubic/tagger';

/* global code_snippets_tags */

(function () {
	const tags_field = document.getElementById('snippet_tags');

	if (tags_field) {
		tagger(tags_field, {
			completion: {list: code_snippets_tags.available_tags},
			allow_spaces: code_snippets_tags.allow_spaces,
			allow_duplicates: false,
			link: name => false
		});
	}
})();
