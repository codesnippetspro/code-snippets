import './store';
import Select from 'react-select';

(function (wp) {
	const {__, _x} = wp.i18n;
	const {registerBlockType} = wp.blocks;
	const {withSelect} = wp.data;
	const {PanelBody, ToggleControl, Placeholder} = wp.components;
	const {InspectorControls} = wp.blockEditor;
	const {serverSideRender: ServerSideRender} = wp;

	/**
	 * Fetch a list of snippet information using the REST API.
	 * @returns {Object}
	 */
	const fetchSnippets = () =>
		withSelect(select => ({snippets: select('code-snippets/snippets-data').receiveSnippetsData()}));

	/**
	 * Create a list of Select options from an array of snippets.
	 * @param {[Object]} snippets
	 * @param {String} type
	 * @returns {[Object]}
	 */
	const buildOptions = (snippets, type) => {
		let options = [];

		for (let i = 0; i < snippets.length; i++) {
			if ('' === type || type === snippets[i]['type']) {
				options.push({
					value: snippets[i]['id'],
					label: snippets[i]['name']
				});
			}
		}
		return options;
	};

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
							options={buildOptions(snippets, 'html')}
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
		edit: fetchSnippets()(({attributes, setAttributes, snippets}) =>
			<div>
				{attributes.snippet_id === 0 &&
				<Placeholder className='code-snippets-source-block' icon='shortcode' label={__('Snippet Source Code', 'code-snippets')}>
					<form>
						<Select
							name='snippet-select'
							className='code-snippets-large-select'
							options={buildOptions(snippets, '')}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to display…', 'code-snippets')}
							onChange={option => setAttributes({snippet_id: option.value})} />
					</form>
				</Placeholder>
				||
				<ServerSideRender
					block="code-snippets/source"
					attributes={attributes} />}
			</div>
		),
		save: () => null,
	});

}(window.wp));


