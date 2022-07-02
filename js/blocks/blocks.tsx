import React from 'react';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { MenuItem, PanelBody, Placeholder, ToggleControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import { Snippet } from '../types';
import './store';

/**
 * Fetch a list of snippet information using the REST API.
 *
 * @return Snippet[] List of snippets.
 */
const fetchSnippets = () =>
	withSelect(select => ({ snippets: select('code-snippets/snippets-data').receiveSnippetsData() }));

const resetButton = callback =>
	<BlockControls>
		<MenuItem icon="image-rotate" title={__('Choose a different snippet', 'code-snippets')} onClick={callback} />
	</BlockControls>;


registerBlockType('code-snippets/content', {
	title: __('Content Snippet', 'code-snippet'),
	description: __('Include a content code snippet in the post.', 'code-snippet'),
	category: 'code-snippets',
	icon: 'shortcode',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'integer', default: 0 },
		network: { type: 'boolean', default: false },
		php: { type: 'boolean', default: false },
		format: { type: 'boolean', default: false },
		shortcodes: { type: 'boolean', default: false },
		debug: { type: 'boolean', default: false }
	},
	edit: fetchSnippets()(({ attributes, setAttributes, snippets }) => {
		const toggleAttribute = atts => setAttributes({ [atts]: !attributes[atts] });

		const addDebugAttribute = atts => {
			atts.debug = true;
			return atts;
		};

		const renderBlock = () =>
			<ServerSideRender block="code-snippets/content" attributes={addDebugAttribute(attributes)} />;

		const options = snippets.filter(snippet => 'html' === snippet.type && snippet.active);

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
			{resetButton(() => setAttributes({ snippet_id: 0 }))}
			{0 === attributes.snippet_id &&
				<Placeholder className="code-snippets-content-block" icon="shortcode"
				             label={__('Content Snippet', 'code-snippets')}>
					<form>
						<Select
							name="snippet-select"
							className="code-snippets-large-select"
							options={options}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to insert…', 'code-snippets')}
							onChange={option => setAttributes({ snippet_id: option.value })} />
					</form>
				</Placeholder> ||
				renderBlock()}
		</div>;
	}),
	save: () => null,
});

const buildOptions = (snippets: Snippet[]) => {
	const categories = {
		php: { label: __('Functions (PHP)', 'code-snippets'), options: [] },
		html: { label: __('Content (Mixed)', 'code-snippets'), options: [] },
		css: { label: __('Styles (CSS)', 'code-snippets'), options: [] },
		js: { label: __('Scripts (JS)', 'code-snippets'), options: [] }
	};

	// Sort the snippets into the appropriate categories
	for (const snippet of snippets) {
		if (snippet.type in categories) {
			categories[snippet.type].options.push({
				value: snippet.id,
				label: snippet.name
			});
		}
	}

	return Object.values(categories);
};

registerBlockType('code-snippets/source', {
	title: __('Snippet Source Code', 'code-snippet'),
	description: __('Display the source code of a snippet in the post.', 'code-snippet'),
	category: 'code-snippets',
	icon: 'editor-code',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'integer', default: 0 },
		network: { type: 'boolean', default: false },
	},
	edit: fetchSnippets()(({ attributes, setAttributes, snippets }) =>
		<div>
			{resetButton(() => setAttributes({ snippet_id: 0 }))}

			{0 === attributes.snippet_id &&
				<Placeholder className="code-snippets-source-block" icon="shortcode"
				             label={__('Snippet Source Code', 'code-snippets')}>
					<form>
						<Select
							name="snippet-select"
							className="code-snippets-large-select"
							options={buildOptions(snippets)}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to display…', 'code-snippets')}
							onChange={option => setAttributes({ snippet_id: option.value })} />
					</form>
				</Placeholder> ||
				<ServerSideRender block="code-snippets/source" attributes={attributes} />}
		</div>),
	save: () => null,
});
