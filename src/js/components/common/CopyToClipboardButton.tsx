import { Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { Button } from './Button'
import { CopyIcon } from './icons/CopyIcon'
import type { ButtonProps } from './Button'

const TIMEOUT = 1500

enum Status {
	INITIAL,
	PROGRESSING,
	SUCCESS,
	ERROR
}

interface StatusIconProps {
	status: Status
}

const StatusIcon: React.FC<StatusIconProps> = ({ status }) => {
	switch (status) {
		case Status.INITIAL:
			return <CopyIcon aria-hidden="true" />
		case Status.PROGRESSING:
			return <span className="spinner-wrapper" aria-hidden="true"><Spinner /></span>
		case Status.SUCCESS:
			return <span className="dashicons dashicons-yes" aria-hidden="true"></span>
		case Status.ERROR:
			return <span className="dashicons dashicons-warning" aria-hidden="true"></span>
	}
}

export interface CopyToClipboardButtonProps extends ButtonProps {
	text: string
	timeout?: number
}

const STATUS_MESSAGES: Record<Status, string> = {
	[Status.INITIAL]: '',
	[Status.PROGRESSING]: __('Copying to clipboard…', 'code-snippets'),
	[Status.SUCCESS]: __('Copied to clipboard.', 'code-snippets'),
	[Status.ERROR]: __('Failed to copy to clipboard. Please try again.', 'code-snippets')
}

export const CopyToClipboardButton: React.FC<CopyToClipboardButtonProps> = ({
	text,
	timeout = TIMEOUT,
	...props
}) => {
	const [status, setStatus] = useState(Status.INITIAL)
	const clipboard = window.navigator.clipboard as Clipboard | undefined

	const handleClick = () => {
		setStatus(Status.PROGRESSING)

		clipboard?.writeText(text)
			.then(() => {
				setStatus(Status.SUCCESS)
				setTimeout(() => setStatus(Status.INITIAL), timeout)
			})
			.catch((error: unknown) => {
				console.error('Failed to copy text to clipboard.', error)
				setStatus(Status.ERROR)
				setTimeout(() => setStatus(Status.INITIAL), timeout)
			})
	}

	return clipboard && window.isSecureContext
		? <>
			<Button
				className="code-snippets-copy-text"
				onClick={handleClick}
				{...props}
			>
				<StatusIcon status={status} />
				{__('Copy', 'code-snippets')}
			</Button>
			<span
				className="screen-reader-text"
				role={Status.ERROR === status ? 'alert' : 'status'}
				aria-live={Status.ERROR === status ? 'assertive' : 'polite'}
			>
				{STATUS_MESSAGES[status]}
			</span>
		</>
		: null
}
