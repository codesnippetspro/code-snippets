import React, { useState } from 'react'
import { handleUnknownError } from '../../utils/errors'
import type { HTMLAttributes, MouseEventHandler } from 'react'

const TIMEOUT = 3000

export interface CopyToClipboardButtonProps extends HTMLAttributes<HTMLAnchorElement> {
	text: string
	copyIcon?: string
	successIcon?: string
	timeout?: number
}

export const CopyToClipboardButton: React.FC<CopyToClipboardButtonProps> = ({
	text,
	copyIcon = 'clipboard',
	successIcon = 'yes',
	...props
}) => {
	const [isSuccess, setIsSuccess] = useState(false)
	const clipboard = window.navigator.clipboard as Clipboard | undefined

	const clickHandler: MouseEventHandler<HTMLAnchorElement> = event => {
		event.preventDefault()

		clipboard?.writeText(text)
			.then(() => {
				setIsSuccess(true)
				setTimeout(() => setIsSuccess(false), TIMEOUT)
			})
			.catch(handleUnknownError)
	}

	return clipboard
		? <a
			href="#"
			className={`code-snippets-copy-text dashicons dashicons-${isSuccess ? successIcon : copyIcon}`}
			onClick={clickHandler}
			{...props}
		></a>
		: null
}
