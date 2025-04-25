import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import ServerSideRender from '@wordpress/server-side-render'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { ExternalLink, PanelBody, ToggleControl } from '@wordpress/components'
import { useSnippetsAPI } from '../hooks/useSnippetsAPI'
import { handleUnknownError } from '../utils/errors'
import { isNetworkAdmin } from '../utils/screen'
import { getSnippetDisplayName, getSnippetType } from '../utils/snippets/snippets'
import { SnippetSelector } from './SnippetSelector'
import type { SelectOptions } from '../types/SelectOption'
import type { BlockConfiguration, BlockEditProps } from '@wordpress/blocks'
import type { Snippet } from '../types/Snippet'

export const CONTENT_BLOCK = 'code-snippets/content'

const buildOptions = (snippets: Snippet[]): SelectOptions<Snippet> =>
	snippets
		.filter(snippet => 'html' === getSnippetType(snippet) && snippet.active)
		.map(snippet => ({
			key: `${snippet.id}-${snippet.network}`,
			value: snippet,
			label: getSnippetDisplayName(snippet)
		}))

export interface ContentBlockAttributes {
	snippet_id: number
	network: boolean
	php?: boolean
	format?: boolean
	shortcodes?: boolean
	debug?: boolean
	className?: string
}

const Edit: React.FC<BlockEditProps<ContentBlockAttributes>> = ({ setAttributes, attributes }) => {
	const blockProps = useBlockProps()
	const { fetchAll } = useSnippetsAPI()
	const [options, setOptions] = useState<SelectOptions<Snippet>>([])

	useEffect(() => {
		fetchAll(isNetworkAdmin())
			.then(snippets => setOptions(buildOptions(snippets)))
			.catch(handleUnknownError)
	}, [fetchAll])

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Processing Options', 'code-snippets')}>
					<ToggleControl
						label={__('Run PHP code', 'code-snippets')}
						checked={attributes.php}
						onChange={isChecked => setAttributes({ php: isChecked })}
					/>
					<ToggleControl
						label={__('Add paragraphs and formatting', 'code-snippets')}
						checked={attributes.format}
						onChange={isChecked => setAttributes({ format: isChecked })}
					/>
					<ToggleControl
						label={__('Enable embedded shortcodes', 'code-snippets')}
						checked={attributes.shortcodes}
						onChange={isChecked => setAttributes({ shortcodes: isChecked })}
						help={
							<ExternalLink
								href={__('https://help.codesnippets.pro/article/54-content-snippet-options', 'code-snippets')}
							>
								{__('Learn more about these options', 'code-snippets')}
							</ExternalLink>
						}
					/>
				</PanelBody>
			</InspectorControls>

			<SnippetSelector
				icon="shortcode"
				label={__('Content Snippet', 'code-snippets')}
				className="code-snippets-content-block"
				options={options}
				onChange={snippet => setAttributes({ snippet_id: snippet?.id ?? 0 })}
				isValueSelected={0 !== attributes.snippet_id}
				renderContent={() =>
					<ServerSideRender block={CONTENT_BLOCK} attributes={{ ...attributes, debug: true }} />}
			/>
		</div>
	)
}

export const ContentBlock: BlockConfiguration<ContentBlockAttributes> = {
	title: __('Content Snippet', 'code-snippets'),
	description: __('Include a content code snippet in the post.', 'code-snippets'),
	category: 'code-snippets',
	icon: 'shortcode',
	supports: { html: false, className: false, customClassName: false },
	attributes: {
		snippet_id: { type: 'number', default: 0 },
		network: { type: 'boolean', default: false },
		php: { type: 'boolean', default: false },
		format: { type: 'boolean', default: true },
		shortcodes: { type: 'boolean', default: false },
		debug: { type: 'boolean', default: false },
		className: { type: 'string', default: undefined }
	},
	edit: props => <Edit {...props} />,
	save: () => null
}
