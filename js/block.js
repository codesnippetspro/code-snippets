(function (wp) {

	const {__, _x} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {Toolbar, Button, Tooltip, PanelBody, PanelRow, ToggleControl, TextControl} = wp.components;
	const {BlockControls, InspectorControls} = wp.blockEditor;

	registerBlockType('code-snippets/content', {
		title: __('Content Snippet', 'code-snippet'),
		description: __('Include a content code snippet in the post.', 'code-snippet'),
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
			const toggleAttribute = (attribute) => setAttributes({[attribute]: !attributes[attribute]});

			return <div className="wp-block-code-snippets-content components-placeholder">
				{
					<InspectorControls>
						<PanelBody title={__('Processing Options', 'code-snippets')}>
							<ToggleControl
								label={__('Evaluate PHP code', 'code-snippets')}
								checked={attributes.php}
								onChange={() => toggleAttribute('php')} />
							<ToggleControl
								label={__('Add paragraphs and formatting', 'code-snippets')}
								checked={attributes.format}
								onChange={() => toggleAttribute('format')} />
							<ToggleControl
								label={__('Evaluate shortcode tags', 'code-snippets')}
								checked={attributes.shortcodes}
								onChange={() => toggleAttribute('shortcodes')} />
						</PanelBody>
					</InspectorControls>
				}
				<TextControl
					type='number'
					label={__('Snippet ID', 'code-snippets')}
					value={attributes.snippet_id === 0 ? '' : attributes.snippet_id}
					onChange={(val) => setAttributes({snippet_id: val})}
				/>
			</div>;
		}
	});

}(window.wp));


