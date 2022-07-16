import tagger from '@jcubic/tagger'

export const loadSnippetTagEditor = () => {
	const tags = window.code_snippets_tags
	const tagsField = document.getElementById('snippet_tags')

	if (!tagsField) {
		return
	}

	tagger(tagsField, {
		completion: {
			list: tags.available_tags,
			delay: 400,
			min_length: 2
		},
		allow_spaces: tags.allow_spaces,
		allow_duplicates: false,
		link: () => false
	})
}
