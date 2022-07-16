import React, { useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components'
import { BlockConfiguration } from '@wordpress/blocks'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { shortcode } from '@wordpress/icons'
import { SnippetData } from '../types/snippet'
import { SnippetSelectGroup, SnippetSelector } from './components'
import { useSnippetData } from './store'

export const SOURCE_BLOCK = 'code-snippets/source'

const buildOptions = (snippets: SnippetData[]): SnippetSelectGroup[] => {
	const categories: Record<string, SnippetSelectGroup> = {
		php: { label: __('Functions (PHP)', 'code-snippets'), options: [] },
		html: { label: __('Content (Mixed)', 'code-snippets'), options: [] },
		css: { label: __('Styles (CSS)', 'code-snippets'), options: [] },
		js: { label: __('Scripts (JS)', 'code-snippets'), options: [] }
	}

	for (const snippet of snippets) {
		if (snippet.type in categories) {
			categories[snippet.type].options.push({
				value: snippet.id,
				label: snippet.name
			})
		}
	}

	return Object.values(categories)
}

interface SourceBlockAttributes {
	snippet_id: number
	network: boolean
	line_numbers: boolean
	highlight_lines: string
	className: string
}

const renderSnippetSource = (snippet: SnippetData, { className, line_numbers, highlight_lines }: SourceBlockAttributes) => {
	const language = 'css' === snippet.type ? 'css' : 'js' === snippet.type ? 'js' : 'php'
	const codeClassNames = [
		`language-${language}`,
		...line_numbers ? ['line-numbers'] : []
	]

	useEffect(() => {
		window.code_snippets_prism?.highlightAll()
	}, [snippet.type, snippet.code, line_numbers, highlight_lines, className])

	return (
		<div className={className}>
			<pre
				id={`code-snippets-source-${snippet.id}`}
				className={line_numbers ? 'linkable-line-numbers' : ''}
				data-line={highlight_lines}
			>
				<code className={codeClassNames.join(' ')}>
					{'php' === snippet.type ? `<?php\n\n${snippet.code}` : snippet.code}
				</code>
			</pre>
		</div>
	)
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
		highlight_lines: { type: 'string', default: '' },
		className: { type: 'string' }
	},
	edit: ({ attributes, setAttributes }) => {
		const blockProps = useBlockProps()
		const snippets = useSnippetData()
		const snippet = 0 !== attributes.snippet_id ? snippets.find(snippet => snippet.id === attributes.snippet_id) : undefined

		return (
			<div {...blockProps}>
				<InspectorControls>
					<PanelBody title={__('Options', 'code-snippets')}>
						<ToggleControl
							label={__('Show line numbers', 'code-snippets')}
							checked={attributes.line_numbers}
							onChange={isChecked => setAttributes({ ...attributes, line_numbers: isChecked })} />
						<TextControl
							label={__('Highlight lines', 'code-snippets')}
							value={attributes.highlight_lines}
							onChange={value => setAttributes({ ...attributes, highlight_lines: value })} />
					</PanelBody>
				</InspectorControls>

				<SnippetSelector
					icon={shortcode}
					label={__('Snippet Source Code', 'code-snippets')}
					className="code-snippets-source-block"
					options={buildOptions(snippets)}
					attributes={attributes}
					setAttributes={setAttributes}
					renderContent={() => snippet ? renderSnippetSource(snippet, attributes) : <></>}
				/>
			</div>
		)
	},
	save: () => null,
}
