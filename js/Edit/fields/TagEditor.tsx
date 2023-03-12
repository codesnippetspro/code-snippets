import tagger from '@jcubic/tagger'
import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'

export const TagEditor: React.FC<BaseSnippetProps> = ({ snippet, setSnippetField }) => {
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
	}, [])

	useEffect(() => {
		if (inputRef.current) {
			setSnippetField('tags', inputRef.current.value.split(/\s*,\s*/))
		}
	}, [inputRef.current])

	return options?.enabled ?
		<>
			<h2 style={{ margin: '25px 0 10px' }}>
				<label htmlFor="snippet_tags" style={{ cursor: 'auto' }}>
					{__('Tags', 'code-snippets')}
				</label>
			</h2>

			<input
				ref={inputRef}
				type="text"
				id="snippet_tags"
				name="snippet_tags"
				style={{ width: '100%' }}
				placeholder={__('Enter a list of tags; separated by commas', 'code-snippets')}
				value={snippet.tags.join(', ')}
			/>
		</> :
		null
}
