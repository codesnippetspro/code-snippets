import './store';

(function (wp) {
	const {__} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {withSelect} = wp.data;
	const {PanelBody, ToggleControl, SelectControl, Placeholder} = wp.components;
	const {InspectorControls, BlockIcon} = wp.blockEditor;

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

		edit: withSelect(select => ({snippets: select('code-snippets/snippets-data').receiveSnippetsData()}))
		(({attributes, setAttributes, snippets}) => {
			const toggleAttribute = (attribute) => setAttributes({[attribute]: !attributes[attribute]});

			let options = [{
				value: 0,
				label: __('Select a snippet to display', 'code-snippets'),
				disabled: true,
			}];

			for (let i = 0; i < snippets.length; i++) {
				const snippet = snippets[i];

				if ('html' === snippet['type']) {
					options.push({
						value: snippet['id'],
						label: snippet['name'],
						disabled: false
					});
				}
			}
			return <div>
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

				<Placeholder className='code-snippets-content-block' icon='shortcode' label={__('Content Snippet', 'code-snippets')}>
					<form>
						<SelectControl
							className='code-snippets-large-select'
							label={__('Select snippet:')}
							hideLabelFromVision={true}
							options={options}
							value={attributes.snippet_id}
							onChange={val => setAttributes({snippet_id: val})} />
					</form>
				</Placeholder>
			</div>
		}),

		save: () => null,
	});
}(window.wp));


