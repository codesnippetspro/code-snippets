import tagger from '@jcubic/tagger'
import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { SnippetInputProps } from '../../types/SnippetInputProps'

export const TagEditor: React.FC<SnippetInputProps> = ({ snippet, setSnippet }) => {
	const options = window.CODE_SNIPPETS_EDIT?.tagOptions
	const inputRef = useRef<HTMLInputElement>(null)

	useEffect(() => {
		if (inputRef.current && options?.enabled) {
			tagger(inputRef.current, {
				completion: {
					list: options.availableTags,
					delay: 400,
					min_length: 2
				},
				allow_spaces: options.allowSpaces,
				allow_duplicates: false,
				link: () => false
			})
		}
	}, [options, inputRef])

	useEffect(() => {
		if (inputRef.current) {
			const tags = inputRef.current.value.split(/\s*,\s*/)
			setSnippet(previous => ({ ...previous, tags }))
		}
	}, [inputRef, setSnippet])

	return options?.enabled ?
		<div className="snippet-tags-container">
			<h2>
				<label htmlFor="snippet_tags">
					{__('Tags', 'code-snippets')}
				</label>
			</h2>

			<input
				ref={inputRef}
				type="text"
				id="snippet_tags"
				name="snippet_tags"
				placeholder={__('Enter a list of tags; separated by commas', 'code-snippets')}
				value={snippet.tags.join(', ')}
			/>
		</div> :
		null
}
