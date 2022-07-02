import React from 'react';
import { __ } from '@wordpress/i18n';
import { BlockConfiguration } from '@wordpress/blocks';
import { SnippetData } from '../types';
import { SnippetSelectGroup, SnippetSelector } from './components';
import { selectSnippetsData } from './store';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

export const SOURCE_BLOCK = 'code-snippets/source'

const buildOptions = (snippets: SnippetData[]): SnippetSelectGroup[] => {
	const categories: Record<string, SnippetSelectGroup> = {
		php: { label: __('Functions (PHP)', 'code-snippets'), options: [] },
		html: { label: __('Content (Mixed)', 'code-snippets'), options: [] },
		css: { label: __('Styles (CSS)', 'code-snippets'), options: [] },
		js: { label: __('Scripts (JS)', 'code-snippets'), options: [] }
	};

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

interface SourceBlockAttributes {
	snippet_id: number
	network: boolean
	line_numbers: boolean
}

export const SourceBlock: BlockConfiguration<SourceBlockAttributes> = {
	title: __('Snippet Source Code', 'code-snippet'),
	description: __('Display the source code of a snippet in the post.', 'code-snippet'),
	category: 'code-snippets',
	icon: 'editor-code',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'number', default: 0 },
		network: { type: 'boolean', default: false },
		line_numbers: { type: 'boolean', default: true }
	},
	edit: ({ attributes, setAttributes }) => {
		const snippets = selectSnippetsData()
		const options = buildOptions(snippets)

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Options', 'code-snippets')}>
						<ToggleControl
							label={__('Show line numbers', 'code-snippets')}
							checked={attributes.line_numbers}
							onChange={() => setAttributes({ ...attributes, line_numbers: !attributes.line_numbers })} />
					</PanelBody>
				</InspectorControls>

				<SnippetSelector
					block={SOURCE_BLOCK}
					icon="shortcode"
					label={__('Snippet Source Code', 'code-snippets')}
					className="code-snippets-source-block"
					options={options}
					attributes={attributes}
					setAttributes={setAttributes}
				/>
			</>
		)
	},
	save: () => null,
};
