import React from 'react'
import { __ } from '@wordpress/i18n'
import { FormTokenField } from '@wordpress/components'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return options?.enabled ?
		<div className="snippet-tags-container">
			<h4><label>{__('Tags', 'code-snippets')}</label></h4>

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
		</div> :
		null
}
