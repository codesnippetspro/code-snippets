import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { ReactNode } from 'react'

export interface DismissibleNoticeProps {
	classNames?: classnames.Argument
	onDismiss: VoidFunction
	children?: ReactNode
	autoHide?: boolean
}

export const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ classNames, onDismiss, children }) =>
	<div id="message" className={classnames('notice fade is-dismissible', classNames)}>
		<>{children}</>

		<button type="button" className="notice-dismiss" onClick={event => {
			event.preventDefault()
			onDismiss()
		}}>
			<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
		</button>
	</div>
