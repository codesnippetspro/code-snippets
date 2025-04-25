import classnames from 'classnames'
import React, { useEffect, useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { PanelBody, Spinner, TextControl, ToggleControl } from '@wordpress/components'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { shortcode } from '@wordpress/icons'
import { useSnippetsAPI } from '../hooks/useSnippetsAPI'
import { handleUnknownError } from '../utils/errors'
import { isNetworkAdmin } from '../utils/screen'
import { buildSnippetSelectOptionGroups, getSnippetType, isCondition } from '../utils/snippets/snippets'
import { SnippetSelector } from './SnippetSelector'
import type { SelectGroup } from '../types/SelectOption'
import type { Snippet, SnippetType } from '../types/Snippet'
import type { BlockConfiguration, BlockEditProps } from '@wordpress/blocks'

export const SOURCE_BLOCK = 'code-snippets/source'

export interface SourceBlockAttributes {
	network: boolean
	className?: string
	snippet_id: number
	line_numbers: boolean
	highlight_lines: string
}

const typeLanguageMap: Record<SnippetType, string> = {
	php: 'php',
	html: 'php',
	css: 'css',
	js: 'js',
	cond: 'json'
}

interface SnippetSourceCodeProps {
	snippet: Snippet
	attributes: SourceBlockAttributes
}

const SnippetSourceCode: React.FC<SnippetSourceCodeProps> = ({
	snippet: { code, id, scope },
	attributes: { className, line_numbers, highlight_lines }
}) => {
	const type = getSnippetType({ scope })

	useEffect(() => {
		window.CODE_SNIPPETS_PRISM?.highlightAll()
	}, [scope, code, line_numbers, highlight_lines, className])

	return (
		<div className={className}>
			<pre
				id={`code-snippets-source-${id}`}
				className={line_numbers ? 'linkable-line-numbers' : undefined}
				data-line={'' === highlight_lines ? undefined : highlight_lines}
			>
				<code className={classnames(`language-${typeLanguageMap[type]}`, { 'line-numbers': line_numbers })}>
					{'php' === type ? `<?php\n\n${code}` : code}
				</code>
			</pre>
		</div>
	)
}

const Edit: React.FC<BlockEditProps<SourceBlockAttributes>> = ({ attributes, setAttributes }) => {
	const blockProps = useBlockProps()
	const { fetchAll } = useSnippetsAPI()
	const [options, setOptions] = useState<SelectGroup<Snippet>[]>([])
	const [snippets, setSnippets] = useState<Snippet[]>()

	useEffect(() => {
		if (!snippets) {
			fetchAll(isNetworkAdmin())
				.then(snippetsList => {
					setSnippets(snippetsList)
					setOptions(buildSnippetSelectOptionGroups(snippetsList.filter(snippet => !isCondition(snippet))))
				})
				.catch(handleUnknownError)
		}
	}, [fetchAll, snippets])

	const snippet = useMemo<Snippet | undefined>(
		() => 0 === attributes.snippet_id
			? undefined
			: snippets?.find(({ id }) => id === attributes.snippet_id),
		[attributes.snippet_id, snippets]
	)

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Options', 'code-snippets')}>
					<ToggleControl
						label={__('Show line numbers', 'code-snippets')}
						checked={attributes.line_numbers}
						onChange={isChecked => setAttributes({ line_numbers: isChecked })} />
					<TextControl
						label={__('Highlight lines', 'code-snippets')}
						value={attributes.highlight_lines}
						placeholder="1, 3-6"
						onChange={value => setAttributes({ highlight_lines: value })} />
				</PanelBody>
			</InspectorControls>

			<SnippetSelector
				icon={shortcode}
				label={__('Snippet Source Code', 'code-snippets')}
				className="code-snippets-source-block"
				options={options}
				onChange={snippet => setAttributes({ snippet_id: snippet?.id ?? 0 })}
				isValueSelected={0 !== attributes.snippet_id}
				renderContent={() => snippet
					? <SnippetSourceCode snippet={snippet} attributes={attributes} />
					: <Spinner />}
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
