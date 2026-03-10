import { createInterpolateElement } from '@wordpress/element'
import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../WithSnippetFormContext'
import { DismissibleNotice } from '../../../common/Notice'

const DESCRIPTION_INDEX = 2
const DETAILS_INDEX = 3
const hasStackTrace = (notice?: readonly unknown[]): boolean => Boolean(notice?.[DETAILS_INDEX])

interface NoticesProps {
	placement: 'above-form' | 'sidebar'
}

export const Notices: React.FC<NoticesProps> = ({ placement }) => {
	const { currentNotice, setCurrentNotice, snippet, setSnippet } = useSnippetForm()
	const showCurrentNotice = 'above-form' === placement ? hasStackTrace(currentNotice) : !hasStackTrace(currentNotice)
	const showCodeErrorNotice = 'sidebar' === placement

	return <>
		{showCurrentNotice && currentNotice
			? <DismissibleNotice className={currentNotice[0]} onDismiss={() => setCurrentNotice(undefined)}>
				<p>{createInterpolateElement(currentNotice[1], { strong: <strong /> })}</p>
				{currentNotice[DESCRIPTION_INDEX]
					? <p>{createInterpolateElement(currentNotice[DESCRIPTION_INDEX], { strong: <strong /> })}</p>
					: null}
				{currentNotice[DETAILS_INDEX]
					? <details>
						<summary>{__('View stack trace', 'code-snippets')}</summary>
						<pre>{currentNotice[DETAILS_INDEX]}</pre>
					</details>
					: null}
			</DismissibleNotice>
			: null}

		{showCodeErrorNotice && !currentNotice && snippet.code_error
			? <DismissibleNotice
				className="notice-error"
				onDismiss={() => setSnippet(previous => ({ ...previous, code_error: null, code_error_trace: null }))}
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
