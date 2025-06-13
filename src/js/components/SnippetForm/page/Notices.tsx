import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { DismissibleNotice } from '../../DismissableNotice'

export const Notices: React.FC = () => {
	const { currentNotice, setCurrentNotice, snippet, setSnippet } = useSnippetForm()

	return <>
		{currentNotice
			? <DismissibleNotice classNames={currentNotice[0]} onRemove={() => setCurrentNotice(undefined)}>
				<p>{currentNotice[1]}</p>
			</DismissibleNotice>
			: null}

		{snippet.code_error
			? <DismissibleNotice
				classNames="error"
				onRemove={() => setSnippet(previous => ({ ...previous, code_error: null }))}
				autoHide={false}
			>
				<p>
					<strong>{sprintf(
						// translators: %d: line number.
						__('Snippet automatically deactivated due to an error on line %d:', 'code-snippets'),
						snippet.code_error[1]
					)}</strong>

					<blockquote>{snippet.code_error[0]}</blockquote>
				</p>
			</DismissibleNotice>
			: null}
	</>
}
