import React from 'react'
import { __ } from '@wordpress/i18n'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'

export const DescriptionEditorProps: React.FC<BaseSnippetProps> = ({ snippet, setSnippetField }) =>
	window.CODE_SNIPPETS_EDIT?.enableDescription ?
		<>
			<h2>
				<label htmlFor="snippet_description">
					{__('Description', 'code-snippets')}
				</label>
			</h2>

			{/* TODO: Add proper visual editor somehow*/}
			<textarea
				id="snippet_description"
				style={{ width: '100%' }}
				onChange={event => setSnippetField('desc', event.target.value)}
			>{snippet.desc}</textarea>
		</> :
		null
