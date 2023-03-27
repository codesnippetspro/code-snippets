import React from 'react'
import { __ } from '@wordpress/i18n'
import { SnippetInputProps } from '../../types/SnippetInputProps'

export const NameInput: React.FC<SnippetInputProps> = ({ snippet, setSnippet, isReadOnly }) =>
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
				disabled={isReadOnly}
				placeholder={__('Enter title here', 'code-snippets')}
				onChange={event => setSnippet(previous => ({ ...previous, name: event.target.value }))}
			/>
		</div>
	</div>
