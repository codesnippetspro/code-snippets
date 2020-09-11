(function (wp) {

	const {__, _x} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {TextControl} = wp.components;

	registerBlockType('code-snippets/content', {
		title: __('Content Snippet', 'code-snippet'),
		description: __('Insert a content snippet.', 'code-snippet'),
		category: 'widgets',
		icon: 'shortcode',
		supports: {
			html: false,
			className: false,
			customClassName: false,
		},
		attributes: {
			snippet_id: {type: 'integer', default: 0},
			network: {type: 'boolean', default: false},
			php: {type: 'boolean', default: false},
			format: {type: 'boolean', default: false},
			shortcodes: {type: 'boolean', default: false}
		},

		edit: ({attributes, setAttributes}) => {

			return (
				<div className="wp-block-code-snippets-content components-placeholder">
					<TextControl
						type='number'
						label={__('Snippet ID', 'code-snippets')}
						value={attributes.snippet_id}
						placeholder={__('Write shortcode here…')}
						onChange={(val) => setAttributes({snippet_id: val})}
					/>
				</div>
			);
		}
	});

}(window.wp));


