import tagger from '@jcubic/tagger';

(tags => {
	const tags_field = document.getElementById('snippet_tags');
	if (!tags_field) return;

	tagger(tags_field, {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore completion option is circular
		completion: {list: tags.available_tags},
		allow_spaces: tags.allow_spaces,
		allow_duplicates: false,
		link: () => false
	});

})(window.code_snippets_tags);
