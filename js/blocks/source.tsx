import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { BlockConfiguration } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { shortcode } from '@wordpress/icons';
import { SnippetData } from '../types';
import { SnippetSelectGroup, SnippetSelector } from './components';
import { selectSnippetsData } from './store';

export const SOURCE_BLOCK = 'code-snippets/source'

const THEMES = {
	default: __('Default', 'code-snippets'),
	dark: __('Dark', 'code-snippets'),
	funky: __('Funky', 'code-snippets'),
	okaidia: __('Okaidia', 'code-snippets'),
	twilight: __('Twilight', 'code-snippets'),
	coy: __('Coy', 'code-snippets'),
	solarizedlight: __('Solarized Light', 'code-snippets'),
	tomorrow: __('Tomorrow Night', 'code-snippets'),
} as const

const isTheme = (theme: unknown): theme is keyof typeof THEMES =>
	'string' === typeof theme && theme in THEMES

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
	theme: keyof typeof THEMES
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
		line_numbers: { type: 'boolean', default: true },
		theme: { type: 'string', default: 'default' }
	},
	edit: ({ attributes, setAttributes }) => {
		const snippets = selectSnippetsData();
		const options = buildOptions(snippets);
		const themeOptions: SelectControl.Option[] = Object.entries(THEMES).map(([value, label]) => ({ label, value }));

		useEffect(() => {
			if (window.Prism) {
				window.Prism.highlightAll();
			}
		});

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Options', 'code-snippets')}>
						<ToggleControl
							label={__('Show line numbers', 'code-snippets')}
							checked={attributes.line_numbers}
							onChange={() => setAttributes({ ...attributes, line_numbers: !attributes.line_numbers })} />
						<SelectControl
							label={__('Theme', 'code-snippets')}
							options={themeOptions}
							multiple={false}
							onChange={value => setAttributes({ ...attributes, theme: isTheme(value) ? value : 'default' })} />
					</PanelBody>
				</InspectorControls>

				<SnippetSelector
					block={SOURCE_BLOCK}
					icon={shortcode}
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
