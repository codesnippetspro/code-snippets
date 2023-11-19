import classnames from 'classnames'
import React, { MouseEventHandler, ReactNode, useEffect } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { useSnippetForm } from '../SnippetForm/context'

interface DismissibleNoticeProps {
	classNames?: classnames.Argument
	onRemove: MouseEventHandler<HTMLButtonElement>
	children?: ReactNode
}

const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ classNames, onRemove, children }) => {

	useEffect(() => {
		if (window.CODE_SNIPPETS_EDIT?.scrollToNotices) {
			window.scrollTo({ top: 0, behavior: 'smooth' })
		}
	}, [])

	return (
		<div id="message" className={classnames('notice fade is-dismissible', classNames)}>
			<>{children}</>

			<button type="button" className="notice-dismiss" onClick={event => {
				event.preventDefault()
				onRemove(event)
			}}>
				<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
			</button>
		</div>
	)
}

export const Notices: React.FC = () => {
	const { currentNotice, setCurrentNotice, snippet, setSnippet } = useSnippetForm()

	return <>
		{currentNotice ?
			<DismissibleNotice classNames={currentNotice[0]} onRemove={() => setCurrentNotice(undefined)}>
				<p>{currentNotice[1]}</p>
			</DismissibleNotice> :
			null}

		{snippet.code_error ?
			<DismissibleNotice
				classNames="error"
				onRemove={() => setSnippet(previous => ({ ...previous, code_error: null }))}
			>
				<p>
					<strong>{sprintf(
						// translators: %d: line number.
						__('Snippet automatically deactivated due to an error on line %d:', 'code-snippets'),
						snippet.code_error[1]
					)}</strong>

					<blockquote>{snippet.code_error[0]}</blockquote>
				</p>
			</DismissibleNotice> :
			null}
	</>
}
