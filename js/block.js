(function (wp) {

	const {__} = wp.i18n;
	const el = wp.element.createElement;
	const {registerBlockType} = wp.blocks;

	registerBlockType('code-snippets/content', {
		title: __('Content Snippet', 'code-snippet'),
		description: __('Insert a content snippet.', 'code-snippet'),
		category: 'widget',
		icon: 'shortcode',
		supports: {
			// Removes support for an HTML mode.
			html: false,
		},

		edit: function (props) {
			return el(
				'select',
				{className: props.className},
				'Hello World, step 1 (from the editor).'
			);
		},

		save: function () {
			return el(
				'p',
				{},
				'Hello World, step 1 (from the front-end).'
			);
		},
	});

}(window.wp));


