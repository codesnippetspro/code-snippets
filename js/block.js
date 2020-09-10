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

		edit: () => {
			return <div> Hello in Editor. </div>;
		},

		save: () => {
			return <div> Hello in Save.</div>;
		},
	});

}(window.wp));


