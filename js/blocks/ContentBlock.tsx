import React, { useMemo } from 'react'
import { Options } from 'react-select'
import { __ } from '@wordpress/i18n'
import { InspectorControls, useBlockProps } from '@wordpress/block-editor'
import { ExternalLink, PanelBody, ToggleControl } from '@wordpress/components'
import { BlockConfiguration, BlockEditProps } from '@wordpress/blocks'
import { useSnippets } from '../utils/api/snippets'
import { getSnippetType } from '../utils/snippets'
import { SnippetSelectOption, SnippetSelector } from './SnippetSelector'
import { Snippet } from '../types/Snippet'
import ServerSideRender from '@wordpress/server-side-render'

export const CONTENT_BLOCK = 'code-snippets/content'

const buildOptions = (snippets: Snippet[]): Options<SnippetSelectOption> =>
	snippets
		.filter(snippet =>
			'html' === getSnippetType(snippet) && snippet.active)
		.map(snippet => ({
			value: snippet.id,
			label: snippet.name
		}))

export interface ContentBlockAttributes {
	snippet_id: number
	network: boolean
	php?: boolean
	format?: boolean
	shortcodes?: boolean
	debug?: boolean
	className: string
}

const Edit: React.FC<BlockEditProps<ContentBlockAttributes>> = ({ setAttributes, attributes }) => {
	const snippets = useSnippets()
	const blockProps = useBlockProps()

	const options = useMemo<Options<SnippetSelectOption>>(
		() => snippets ? buildOptions(snippets) : [],
		[snippets]
	)

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Processing Options', 'code-snippets')}>
					<ToggleControl
						label={__('Run PHP code', 'code-snippets')}
						checked={attributes.php}
						onChange={isChecked => setAttributes({ ...attributes, php: isChecked })}
					/>
					<ToggleControl
						label={__('Add paragraphs and formatting', 'code-snippets')}
						checked={attributes.format}
						onChange={isChecked => setAttributes({ ...attributes, format: isChecked })}
					/>
					<ToggleControl
						label={__('Enable embedded shortcodes', 'code-snippets')}
						checked={attributes.shortcodes}
						onChange={isChecked => setAttributes({ ...attributes, shortcodes: isChecked })}
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
				label={__('Content Snippet', 'code-snippets')}
				className="code-snippets-content-block"
				icon="shortcode"
				options={options}
				attributes={attributes}
				setAttributes={setAttributes}
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
		className: { type: 'string' }
	},
	edit: props => <Edit {...props} />,
	save: () => null
}
