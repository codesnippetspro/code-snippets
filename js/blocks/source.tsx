import React from 'react';
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { BlockConfiguration } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { SnippetData } from '../types';
import { ResetButton, SnippetSelect, SnippetSelectGroup } from './components';
import { selectSnippetsData } from './store';

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

interface BlockAttributes {
	snippet_id: number,
	network: boolean
}

export const SourceBlock: BlockConfiguration<BlockAttributes> = {
	title: __('Snippet Source Code', 'code-snippet'),
	description: __('Display the source code of a snippet in the post.', 'code-snippet'),
	category: 'code-snippets',
	icon: 'editor-code',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'number', default: 0 },
		network: { type: 'boolean', default: false },
	},
	edit: ({ attributes, setAttributes }) => {
		const snippets = selectSnippetsData()
		const options = buildOptions(snippets)
		const initialValue = snippets.find(snippet => snippet.id === attributes.snippet_id)

		return (
			<>
				<ResetButton onClick={() => setAttributes({ snippet_id: 0 })} />

				{0 === attributes.snippet_id ?
					<Placeholder className="code-snippets-source-block" icon="shortcode"
					             label={__('Snippet Source Code', 'code-snippets')}>
						<form>
							<SnippetSelect
								options={options}
								value={initialValue ? { value: initialValue.id, label: initialValue.name } : undefined}
								setAttributes={setAttributes}
							/>
						</form>
					</Placeholder> :
					<ServerSideRender block="code-snippets/source" attributes={attributes} />}
			</>
		);
	},
	save: () => null,
};
