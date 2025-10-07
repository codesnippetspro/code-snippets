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
			return <CopyIcon />
		case Status.PROGRESSING:
			return <span className="spinner-wrapper"><Spinner /></span>
		case Status.SUCCESS:
			return <span className="dashicons dashicons-yes"></span>
		case Status.ERROR:
			return <span className="dashicons dashicons-warning"></span>
	}
}

export interface CopyToClipboardButtonProps extends ButtonProps {
	text: string
	timeout?: number
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
			})
	}

	return clipboard && window.isSecureContext
		? <Button
			className="code-snippets-copy-text"
			onClick={handleClick}
			{...props}
		>
			<StatusIcon status={status} />
			{__('Copy', 'code-snippets')}
		</Button>
		: null
}
