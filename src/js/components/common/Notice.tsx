import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React from 'react'
import type { HTMLAttributes, ReactNode } from 'react'

export type NoticeType = 'info' | 'warning' | 'error' | 'success'

export interface NoticeProps extends Omit<HTMLAttributes<HTMLDivElement>, 'className'> {
	className?: classnames.Argument
	children?: ReactNode
	type?: NoticeType
}

const isAssertive = (type?: NoticeType): boolean => 'error' === type || 'warning' === type

export const Notice: React.FC<NoticeProps> = ({ className, type, children, ...props }) =>
	<div
		className={classnames('notice', { [`notice-${type}`]: type }, className)}
		role={isAssertive(type) ? 'alert' : 'status'}
		aria-live={isAssertive(type) ? 'assertive' : 'polite'}
		{...props}
	>
		{children}
	</div>

export interface DismissibleNoticeProps extends NoticeProps {
	onDismiss: VoidFunction
}

export const DismissibleNotice: React.FC<DismissibleNoticeProps> = ({ className, onDismiss, children, ...noticeProps }) =>
	<Notice className={classnames('is-dismissible', className)} {...noticeProps}>
		{children}

		<button type="button" className="notice-dismiss" onClick={event => {
			event.preventDefault()
			onDismiss()
		}}>
			<span className="screen-reader-text">{__('Dismiss notice.', 'code-snippets')}</span>
		</button>
	</Notice>
