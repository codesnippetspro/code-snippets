import React from 'react';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { Snippet } from '../types';
import { fetchSnippets, ResetButton } from './common';

const buildOptions = (snippets: Snippet[]) => {
	const categories = {
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
			<ResetButton onClick={() => setAttributes({ snippet_id: 0 })} />

			{0 === attributes.snippet_id ?
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
				</Placeholder> :
				<ServerSideRender block="code-snippets/source" attributes={attributes} />}
		</div>),
	save: () => null,
});
