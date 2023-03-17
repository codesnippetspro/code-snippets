import React from 'react'
import { __ } from '@wordpress/i18n'
import { TagEditor } from '../../common/TagEditor'
import { SnippetInputProps } from '../../types/SnippetInputProps'

const options = window.CODE_SNIPPETS_EDIT?.tagOptions

export const TagsInput: React.FC<SnippetInputProps> = ({ snippet, setSnippet, isReadOnly }) =>
	options?.enabled ?
		<div className="snippet-tags-container">
			<h2>
				<label htmlFor="snippet_tags">
					{__('Tags', 'code-snippets')}
				</label>
			</h2>

			<TagEditor
				id="snippet_tags"
				onChange={tags => setSnippet(previous => ({ ...previous, tags }))}
				tags={snippet.tags}
				disabled={isReadOnly}
				completions={options.availableTags}
				allowSpaces={options.allowSpaces}
				placeholder={__('Enter a list of tags; separated by commas', 'code-snippets')}
			/>
		</div> :
		null
