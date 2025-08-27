import React from 'react'
import { __, _x } from '@wordpress/i18n'
import { FormTokenField } from '@wordpress/components'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { ExplainSnippetButton } from './ExplainSnippetButton'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsEditor: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return options?.enabled
		? <div className="snippet-tags-container">
			<h3>
				<label htmlFor="components-form-token-input-0">{__('Snippet Tags', 'code-snippets')}</label>

				<ExplainSnippetButton
					field="tags"
					onResponse={generated => {
						setSnippet(previous => ({
							...previous,
							tags: [...new Set([...previous.tags, ...generated.tags ?? []])]
						}))
					}}
				>
					{_x('Add', 'generate snippet tags', 'code-snippets')}
				</ExplainSnippetButton>
			</h3>

			<FormTokenField
				label=""
				value={snippet.tags}
				disabled={isReadOnly}
				suggestions={options.availableTags}
				tokenizeOnBlur
				tokenizeOnSpace={!options.allowSpaces}
				onChange={tokens => {
					setSnippet(previous => ({
						...previous,
						tags: tokens.map(token => 'string' === typeof token ? token : token.value)
					}))
				}}
			/>
		</div>
		: null
}
