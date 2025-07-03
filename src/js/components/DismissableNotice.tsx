import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { ReactNode } from 'react'

export interface DismissibleNoticeProps {
	className?: classnames.Argument
	onDismiss: VoidFunction
	children?: ReactNode
}

export const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ className, onDismiss, children }) =>
	<div id="message" className={classnames('notice fade is-dismissible', className)}>
		<>{children}</>

		<button type="button" className="notice-dismiss" onClick={event => {
			event.preventDefault()
			onDismiss()
		}}>
			<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
		</button>
	</div>
