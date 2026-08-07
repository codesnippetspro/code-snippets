import React from 'react'
import { __ } from '@wordpress/i18n'
import { FormTokenField } from '@wordpress/components'
import { useSnippetForm } from '../WithSnippetFormContext'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsEditor: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return options?.enabled
		? <div className="snippet-tags-container">
			<FormTokenField
				value={snippet.tags}
				aria-label={__('Snippet Tags', 'code-snippets')}
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
