import { createInterpolateElement } from '@wordpress/element'
import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { DismissibleNotice } from '../../common/DismissableNotice'

export const Notices: React.FC = () => {
	const { currentNotice, setCurrentNotice, snippet, setSnippet } = useSnippetForm()

	return <>
		{currentNotice
			? <DismissibleNotice className={currentNotice[0]} onDismiss={() => setCurrentNotice(undefined)}>
				<p>{createInterpolateElement(currentNotice[1], { strong: <strong /> })}</p>
			</DismissibleNotice>
			: null}

		{snippet.code_error
			? <DismissibleNotice
				className="notice-error"
				onDismiss={() => setSnippet(previous => ({ ...previous, code_error: null }))}
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
