import React, { useCallback, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import domReady from '@wordpress/dom-ready'
import { ExplainSnippetButton } from '../buttons/ExplainSnippetButton'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

export const EDITOR_ID = 'snippet_description'

const TOOLBAR_BUTTONS = [
	[
		'bold',
		'italic',
		'underline',
		'strikethrough',
		'blockquote',
		'bullist',
		'numlist',
		'alignleft',
		'aligncenter',
		'alignright',
		'link',
		'wp_adv',
		'code_snippets'
	],
	[
		'formatselect',
		'forecolor',
		'pastetext',
		'removeformat',
		'charmap',
		'outdent',
		'indent',
		'undo',
		'redo',
		'spellchecker'
	]
]

const initializeEditor = (onChange: (content: string) => void) => {
	window.wp.editor?.initialize(EDITOR_ID, {
		mediaButtons: window.CODE_SNIPPETS_EDIT?.descEditorOptions.mediaButtons,
		quicktags: true,
		tinymce: {
			toolbar: TOOLBAR_BUTTONS.map(line => line.join(' ')),
			setup: editor => {
				editor.on('change', () => onChange(editor.getContent()))
			}
		}
	})
}

export const DescriptionEditor: React.FC = () => {
	const { snippet, setSnippet, updateSnippet, isReadOnly } = useSnippetForm()

	const onChange = useCallback(
		(desc: string) => setSnippet(previous => ({ ...previous, desc })),
		[setSnippet]
	)

	useEffect(() => {
		domReady(() => initializeEditor(onChange))
	}, [onChange])

	return window.CODE_SNIPPETS_EDIT?.enableDescription
		? <div className="snippet-description-container">
			{'' === snippet.code.trim()
				? null
				: <ExplainSnippetButton
					field="desc"
					snippet={snippet}
					disabled={isReadOnly}
					onResponse={generated => {
						updateSnippet(previous => ({
							...previous,
							name: generated.name && '' === previous.name.trim() ? generated.name : previous.name,
							desc: `${previous.desc}${generated.desc ? `\n<p>${generated.desc}</p>` : ''}`
						}))
					}}
				>
					{__('Add Description', 'code-snippets')}
				</ExplainSnippetButton>}

			<h2>
				<label htmlFor={EDITOR_ID}>
					{__('Description', 'code-snippets')}
				</label>
			</h2>

			<textarea
				id={EDITOR_ID}
				className="wp-editor-area"
				onChange={event => onChange(event.target.value)}
				autoComplete="off"
				disabled={isReadOnly}
				rows={window.CODE_SNIPPETS_EDIT.descEditorOptions.rows}
				cols={40}
			>{snippet.desc}</textarea>
		</div>
		: null
}
