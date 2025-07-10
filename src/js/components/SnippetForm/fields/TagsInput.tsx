import React from 'react'
import { __ } from '@wordpress/i18n'
import { TagEditor } from '../../TagEditor'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()

	return options?.enabled
		? <div className="snippet-tags-container">
			<h2>
				<label htmlFor="snippet_tags">
					{__('Tags', 'code-snippets')}
				</label>
			</h2>

			<TagEditor
				id="snippet_tags"
				tags={snippet.tags}
				addOnBlur
				disabled={isReadOnly}
				onChange={tags => setSnippet(previous => ({ ...previous, tags }))}
				completions={options.availableTags}
				allowSpaces={options.allowSpaces}
				placeholder={__('Enter a list of tags; separated by commas.', 'code-snippets')}
			/>
		</div>
		: null
}
