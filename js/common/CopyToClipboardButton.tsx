import React, { HTMLAttributes, MouseEventHandler, useState } from 'react'

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

	const clickHandler: MouseEventHandler<HTMLAnchorElement> = event => {
		event.preventDefault()

		window.navigator.clipboard?.writeText(text)
			.then(() => {
				setIsSuccess(true)
				setTimeout(() => setIsSuccess(false), TIMEOUT)
			})
			.catch(error => console.error(error))
	}

	return window.navigator.clipboard ?
		<a
			href="#"
			className={`code-snippets-copy-text dashicons dashicons-${isSuccess ? successIcon : copyIcon}`}
			onClick={clickHandler}
			{...props}
		></a> :
		null
}
