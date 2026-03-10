import { createInterpolateElement } from '@wordpress/element'
import React, { useEffect, useRef, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../WithSnippetFormContext'
import { DismissibleNotice } from '../../../common/Notice'

const DESCRIPTION_INDEX = 2
const DETAILS_INDEX = 3
const hasStackTrace = (notice?: readonly unknown[]): boolean => Boolean(notice?.[DETAILS_INDEX])

interface NoticesProps {
	placement: 'above-form' | 'sidebar'
}

const StackTraceDetails: React.FC<{ trace: string }> = ({ trace }) => {
	const preRef = useRef<HTMLPreElement>(null)
	const [isOpen, setIsOpen] = useState(false)
	const [showHint, setShowHint] = useState(false)

	useEffect(() => {
		if (!isOpen) {
			setShowHint(false)
			return
		}

		const updateHintVisibility = () => {
			const pre = preRef.current
			setShowHint(Boolean(pre && pre.scrollWidth > pre.clientWidth))
		}

		updateHintVisibility()
		window.addEventListener('resize', updateHintVisibility)

		return () => {
			window.removeEventListener('resize', updateHintVisibility)
		}
	}, [isOpen, trace])

	return (
		<details onToggle={event => setIsOpen(event.currentTarget.open)}>
			<summary>{__('View stack trace', 'code-snippets')}</summary>
			<pre ref={preRef}>{trace}</pre>
			{showHint
				? <p className="stack-trace-hint">
					<em>{__('Scroll horizontally if the trace is cut off.', 'code-snippets')}</em>
				</p>
				: null}
		</details>
	)
}

export const Notices: React.FC<NoticesProps> = ({ placement }) => {
	const { currentNotice, setCurrentNotice, snippet, setSnippet } = useSnippetForm()
	const showCurrentNotice = 'above-form' === placement ? hasStackTrace(currentNotice) : !hasStackTrace(currentNotice)
	const showCodeErrorNotice = 'sidebar' === placement && !snippet.code_error_trace

	return <>
		{showCurrentNotice && currentNotice
			? <DismissibleNotice className={`${currentNotice[0]} code-snippets-notice`} onDismiss={() => setCurrentNotice(undefined)}>
				<p>{createInterpolateElement(currentNotice[1], { strong: <strong /> })}</p>
				{currentNotice[DESCRIPTION_INDEX]
					? <p>{createInterpolateElement(currentNotice[DESCRIPTION_INDEX], { strong: <strong /> })}</p>
					: null}
				{currentNotice[DETAILS_INDEX]
					? <StackTraceDetails trace={currentNotice[DETAILS_INDEX]} />
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
