import tagger from '@jcubic/tagger';
import { window } from '../types'

(tags => {
	const tags_field = document.getElementById('snippet_tags');
	if (!tags_field) return;

	tagger(tags_field, {
		completion: {
			list: tags.available_tags,
			delay: 400,
			min_length: 2
		},
		allow_spaces: tags.allow_spaces,
		allow_duplicates: false,
		link: () => false
	});

})(window.code_snippets_tags);
