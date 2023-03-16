import React, { Dispatch, SetStateAction } from 'react'
import { __ } from '@wordpress/i18n'
import { Notices } from '../types/Notice'

export interface NoticeListProps {
	notices: Notices
	setNotices: Dispatch<SetStateAction<Notices>>
}

export const NoticeList: React.FC<NoticeListProps> = ({ notices, setNotices }) =>
	<>
		{notices.map(([type, message], index) =>
			<div key={index} id="message" className={`notice ${type} fade is-dismissible`}>
				<p>{message}</p>

				<button type="button" className="notice-dismiss" onClick={event => {
					event.preventDefault()
					setNotices(notices.filter((_, i) => index !== i))
				}}>
					<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
				</button>
			</div>
		)}
	</>
