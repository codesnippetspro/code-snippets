import classnames from 'classnames'
import React, { Dispatch, MouseEventHandler, ReactNode, SetStateAction } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { Notices } from '../../types/Notice'
import { SnippetInputProps } from '../../types/SnippetInputProps'

interface DismissibleNoticeProps {
	classNames?: classnames.Argument
	onRemove: MouseEventHandler<HTMLButtonElement>
	children?: ReactNode
}

const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ classNames, onRemove, children }) =>
	<div id="message" className={classnames('notice fade is-dismissible', classNames)}>
		<>{children}</>

		<button type="button" className="notice-dismiss" onClick={event => {
			event.preventDefault()
			onRemove(event)
		}}>
			<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
		</button>
	</div>

export interface NoticeListProps extends SnippetInputProps {
	notices: Notices
	setNotices: Dispatch<SetStateAction<Notices>>
}

export const NoticeList: React.FC<NoticeListProps> = ({ notices, setNotices, snippet, setSnippet }) =>
	<>
		{notices.map(([type, message], index) =>
			<DismissibleNotice
				key={index}
				classNames={type}
				onRemove={() => setNotices(notices.filter((_, i) => index !== i))}
			>
				<p>{message}</p>
			</DismissibleNotice>
		)}

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
