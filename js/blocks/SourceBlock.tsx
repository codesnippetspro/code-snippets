import classnames from 'classnames'
import React, { useEffect, useMemo } from 'react'
import { __ } from '@wordpress/i18n'
import { PanelBody, Spinner, TextControl, ToggleControl } from '@wordpress/components'
import { BlockConfiguration, BlockEditProps } from '@wordpress/blocks'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { shortcode } from '@wordpress/icons'
import { Snippet, SnippetType } from '../types/Snippet'
import { useSnippets } from '../utils/api/snippets'
import { getSnippetType } from '../utils/snippets'
import { SnippetSelectGroup, SnippetSelector } from './SnippetSelector'

export const SOURCE_BLOCK = 'code-snippets/source'

const buildOptions = (snippets: Snippet[]): SnippetSelectGroup[] => {
	const categories: Partial<Record<SnippetType, SnippetSelectGroup>> = {
		php: { label: __('Functions (PHP)', 'code-snippets'), options: [] },
		html: { label: __('Content (Mixed)', 'code-snippets'), options: [] },
		css: { label: __('Styles (CSS)', 'code-snippets'), options: [] },
		js: { label: __('Scripts (JS)', 'code-snippets'), options: [] }
	}

	for (const snippet of snippets) {
		categories[getSnippetType(snippet)]?.options.push({
			value: snippet.id,
			label: snippet.name
		})
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

interface SnippetSourceCodeProps {
	snippet: Snippet
	attributes: SourceBlockAttributes
}

const SnippetSourceCode: React.FC<SnippetSourceCodeProps> = ({
	snippet: { code, id, scope },
	attributes: { className, line_numbers, highlight_lines }
}) => {
	const type = getSnippetType(scope)
	const language = 'css' === type ? 'css' : 'js' === type ? 'js' : 'php'

	useEffect(() => {
		window.CODE_SNIPPETS_PRISM?.highlightAll()
	}, [scope, code, line_numbers, highlight_lines, className])

	return (
		<div className={className}>
			<pre
				id={`code-snippets-source-${id}`}
				className={line_numbers ? 'linkable-line-numbers' : ''}
				data-line={highlight_lines}
			>
				<code className={classnames(`language-${language}`, { 'line-numbers': line_numbers })}>
					{'php' === type ? `<?php\n\n${code}` : code}
				</code>
			</pre>
		</div>
	)
}

const Edit: React.FC<BlockEditProps<SourceBlockAttributes>> = ({ attributes, setAttributes }) => {
	const snippets = useSnippets()
	const blockProps = useBlockProps()

	const snippet = useMemo<Snippet | undefined>(
		() => 0 === attributes.snippet_id ? undefined : snippets?.find(({ id }) => id === attributes.snippet_id),
		[attributes.snippet_id, snippets]
	)

	const options = useMemo<SnippetSelectGroup[]>(
		() => snippets ? buildOptions(snippets) : [],
		[snippets]
	)

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
						placeholder="1, 3-6"
						onChange={value => setAttributes({ ...attributes, highlight_lines: value })} />
				</PanelBody>
			</InspectorControls>

			<SnippetSelector
				icon={shortcode}
				label={__('Snippet Source Code', 'code-snippets')}
				className="code-snippets-source-block"
				options={options}
				attributes={attributes}
				setAttributes={setAttributes}
				renderContent={() => snippet ?
					<SnippetSourceCode snippet={snippet} attributes={attributes} /> : <Spinner />}
			/>
		</div>
	)
}

export const SourceBlock: BlockConfiguration<SourceBlockAttributes> = {
	title: __('Snippet Source Code', 'code-snippets'),
	description: __('Display the source code of a snippet in the post.', 'code-snippets'),
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
	edit: props => <Edit {...props} />,
	save: () => null
}
