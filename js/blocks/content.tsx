import React from 'react';
import { Options } from 'react-select';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { BlockConfiguration } from '@wordpress/blocks';
import { SnippetSelectOption, SnippetSelector } from './components';
import { SnippetData } from '../types';
import { STORE_KEY } from './store';
import { useSelect } from '@wordpress/data';

export const CONTENT_BLOCK = 'code-snippets/content'

const buildOptions = (snippets: SnippetData[]): Options<SnippetSelectOption> =>
	snippets
		.filter(snippet =>
			'html' === snippet.type && snippet.active)
		.map(snippet => ({
			value: snippet.id,
			label: snippet.name
		}))


interface ContentBlockAttributes {
	snippet_id: number
	network: boolean
	php?: boolean
	format?: boolean
	shortcodes?: boolean
	debug?: boolean
	className: string
}

export const ContentBlock: BlockConfiguration<ContentBlockAttributes> = {
	title: __('Content Snippet', 'code-snippet'),
	description: __('Include a content code snippet in the post.', 'code-snippet'),
	category: 'code-snippets',
	icon: 'shortcode',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'number', default: 0 },
		network: { type: 'boolean', default: false },
		php: { type: 'boolean', default: false },
		format: { type: 'boolean', default: false },
		shortcodes: { type: 'boolean', default: false },
		debug: { type: 'boolean', default: false },
		className: { type: 'string' }
	},
	edit: ({ setAttributes, attributes }) => {
		const blockProps = useBlockProps()
		const options = useSelect(select =>
			buildOptions(select(STORE_KEY).receiveSnippetsData()), []);

		return (
			<div {...blockProps}>
				<InspectorControls>
					<PanelBody title={__('Processing Options', 'code-snippets')}>
						<ToggleControl
							label={__('Run PHP code', 'code-snippets')}
							checked={attributes.php}
							onChange={isChecked => setAttributes({...attributes, php: isChecked})} />
						<ToggleControl
							label={__('Add paragraphs and formatting', 'code-snippets')}
							checked={attributes.format}
							onChange={isChecked => setAttributes({...attributes, format: isChecked})} />
						<ToggleControl
							label={__('Enable embedded shortcodes', 'code-snippets')}
							checked={attributes.shortcodes}
							onChange={isChecked => setAttributes({...attributes, shortcodes: isChecked})} />
					</PanelBody>
				</InspectorControls>

				<SnippetSelector
					block={CONTENT_BLOCK}
					label={__('Content Snippet', 'code-snippets')}
					className="code-snippets-content-block"
					icon="shortcode"
					options={options}
					attributes={{ debug: true, ...attributes }}
					setAttributes={setAttributes}
				/>
			</div>
		)
	},
	save: () => null,
}
