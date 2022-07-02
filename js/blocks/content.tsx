import React from 'react';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Placeholder, ToggleControl } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { fetchSnippets, ResetButton } from './common';
import { SnippetData } from '../types';

const buildOptions = (snippets: SnippetData[]) =>
	snippets
		.filter(snippet =>
			'html' === snippet.type && snippet.active)
		.map(snippet => ({
			value: snippet.id,
			label: snippet.name
		}))

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

		const renderBlock = () =>
			<ServerSideRender block="code-snippets/content" attributes={{ debug: true, ...attributes }} />;

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

			<ResetButton onClick={() => setAttributes({ snippet_id: 0 })} />

			{0 === attributes.snippet_id ?
				<Placeholder className="code-snippets-content-block" icon="shortcode"
				             label={__('Content Snippet', 'code-snippets')}>
					<form>
						<Select
							name="snippet-select"
							className="code-snippets-large-select"
							options={buildOptions(snippets)}
							value={attributes.snippet_id}
							placeholder={__('Select a snippet to insert…', 'code-snippets')}
							onChange={option => setAttributes({ snippet_id: option.value })} />
					</form>
				</Placeholder> :
				renderBlock()}
		</div>
	}),
	save: () => null,
});
