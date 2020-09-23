import './store';
import Select from 'react-select';

(function (wp) {
	const {__, _x} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {withSelect} = wp.data;
	const {PanelBody, ToggleControl, Placeholder} = wp.components;
	const {InspectorControls} = wp.blockEditor;
	const {serverSideRender: ServerSideRender} = wp;

	registerBlockType('code-snippets/content', {
		title: __('Content Snippet', 'code-snippet'),
		description: __('Include a content code snippet in the post.', 'code-snippet'),
		category: 'code-snippets',
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

			const renderBlock = () =>
				<ServerSideRender
					block="code-snippets/content"
					attributes={attributes} />;

			let options = [];
			for (let i = 0; i < snippets.length; i++) {
				const snippet = snippets[i];

				if ('html' === snippet['type']) {
					options.push({
						value: snippet['id'],
						label: snippet['name']
					});
				}
			}
			return <div>
				{
					<InspectorControls>
						<PanelBody title={__('Processing Options', 'code-snippets')}>
							<ToggleControl
								label={__('Run PHP code', 'code-snippets')}
								checked={attributes.php}
								onChange={() => toggleAttribute('php')} />
							<ToggleControl
								label={__('Add paragraphs and formatting', 'code-snippets')}
								checked={attributes.format}
								onChange={() => toggleAttribute('format')} />
							<ToggleControl
								label={__('Enable embedded shortcodes', 'code-snippets')}
								checked={attributes.shortcodes}
								onChange={() => toggleAttribute('shortcodes')} />
						</PanelBody>
					</InspectorControls>
				}

				{attributes.snippet_id === 0 &&
				<Placeholder className='code-snippets-content-block' icon='shortcode' label={__('Content Snippet', 'code-snippets')}>
					<form>
						<Select
							name='snippet-select'
							className='code-snippets-large-select'
							options={options}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to insert…', 'code-snippets')}
							onChange={option => setAttributes({snippet_id: option.value})} />
					</form>
				</Placeholder>
				|| renderBlock()}
			</div>
		}),

		save: () => null,
	});
}(window.wp));


