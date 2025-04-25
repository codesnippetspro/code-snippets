import React from 'react'
import { __, _x } from '@wordpress/i18n'
import { FormTokenField } from '@wordpress/components'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { isCondition } from '../../../utils/snippets/snippets'
import { ExplainSnippetButton } from '../actions/ExplainSnippetButton'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return options?.enabled
		? <div className="snippet-tags-container">
			<h4>
				<label>
					{isCondition(snippet)
						? __('Condition Tags', 'code-snippets')
						: __('Tags', 'code-snippets')}
				</label>
			</h4>

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
