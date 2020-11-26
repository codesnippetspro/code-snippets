import './store';
import Select from 'react-select';

(function (wp) {
	const {__, _x} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {withSelect} = wp.data;
	const {PanelBody, ToggleControl, Placeholder, MenuItem} = wp.components;
	const {InspectorControls, BlockControls} = wp.blockEditor;
	const {serverSideRender: ServerSideRender} = wp;

	/**
	 * Fetch a list of snippet information using the REST API.
	 * @returns {Object}
	 */
	const fetchSnippets = () =>
		withSelect(select => ({snippets: select('code-snippets/snippets-data').receiveSnippetsData()}));

	const resetButton = (callback) => (
		<BlockControls>
			<MenuItem icon="image-rotate" title={__('Choose a different snippet', 'code-snippets')} onClick={callback} />
		</BlockControls>
	);

	registerBlockType('code-snippets/content', {
		title: __('Content Snippet', 'code-snippet'),
		description: __('Include a content code snippet in the post.', 'code-snippet'),
		category: 'code-snippets',
		icon: 'shortcode',
		supports: {html: false, className: false, customClassName: false},
		attributes: {
			snippet_id: {type: 'integer', default: 0},
			network: {type: 'boolean', default: false},
			php: {type: 'boolean', default: false},
			format: {type: 'boolean', default: false},
			shortcodes: {type: 'boolean', default: false}
		},
		edit: fetchSnippets()(({attributes, setAttributes, snippets}) => {
			const toggleAttribute = (attribute) => setAttributes({[attribute]: !attributes[attribute]});

			const renderBlock = () =>
				<ServerSideRender
					block="code-snippets/content"
					attributes={attributes} />;

			let options = [];
			for (let i = 0; i < snippets.length; i++) {
				if ('html' !== snippets[i]['type'] || !snippets[i]['active']) continue;
				options.push({value: snippets[i]['id'], label: snippets[i]['name']});
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
				{resetButton(() => setAttributes({snippet_id: 0}))}
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

	registerBlockType('code-snippets/source', {
		title: __('Snippet Source Code', 'code-snippet'),
		description: __('Display the source code of a snippet in the post.', 'code-snippet'),
		category: 'code-snippets',
		icon: 'editor-code',
		supports: {html: false, className: false, customClassName: false},
		attributes: {
			snippet_id: {type: 'integer', default: 0},
			network: {type: 'boolean', default: false},
		},
		edit: fetchSnippets()(({attributes, setAttributes, snippets}) => {

			const buildOptions = () => {
				let categories = {
					php: {label: __('Functions (PHP)', 'code-snippets'), options: []},
					html: {label: __('Content (Mixed)', 'code-snippets'), options: []},
					css: {label: __('Styles (CSS)', 'code-snippets'), options: []},
					js: {label: __('Scripts (JS)', 'code-snippets'), options: []}
				};

				// sort the snippets into the appropriate categories
				for (let i = 0; i < snippets.length; i++) {
					if (categories.hasOwnProperty(snippets[i]['type'])) {
						categories[snippets[i]['type']]['options'].push({
							value: snippets[i]['id'],
							label: snippets[i]['name']
						});
					}
				}

				// convert the object into an array for use with the select library
				let options = [];
				for (let category in categories) {
					if (categories.hasOwnProperty(category)) {
						options.push(categories[category]);
					}
				}

				return options;
			};

			return <div>
				{resetButton(() => setAttributes({snippet_id: 0}))}

				{attributes.snippet_id === 0 &&
				<Placeholder className='code-snippets-source-block' icon='shortcode'
				             label={__('Snippet Source Code', 'code-snippets')}>
					<form>
						<Select
							name='snippet-select'
							className='code-snippets-large-select'
							options={buildOptions()}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to display…', 'code-snippets')}
							onChange={option => setAttributes({snippet_id: option.value})} />
					</form>
				</Placeholder>
				||
				<ServerSideRender
					block="code-snippets/source"
					attributes={attributes} />}
			</div>;
		}),
		save: () => null,
	});

}(window.wp));


