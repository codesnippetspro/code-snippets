import React from 'react'
import { __ } from '@wordpress/i18n'
import { BaseSnippetProps } from '../../types/BaseSnippetProps'

export const NameInput: React.FC<BaseSnippetProps> = ({ snippet, setSnippetField }) =>
	<div id="titlediv">
		<div id="titlewrap">
			<label htmlFor="title" className="screen-reader-text">
				{__('Name', 'code-snippets')}
			</label>
			<input
				id="title"
				type="text"
				name="snippet_name"
				autoComplete="off"
				value={snippet.name}
				placeholder={__('Enter title here', 'code-snippets')}
				onChange={event => setSnippetField('name', event.target.value)}
			/>
		</div>
	</div>
