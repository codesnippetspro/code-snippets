import React from 'react'
import classnames from 'classnames'

//const CLOUD_CHECKOUT_URL = 'https://codesnippets.cloud/api/v1/public/plugin/checkout-init'
const CLOUD_CHECKOUT_URL = 'http://localhost/api/v1/public/plugin/checkout-init'

interface CloudCheckoutButtonProps
	extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
	to?: string // Optional override for URL
	primary?: boolean
	secondary?: boolean
	small?: boolean
	large?: boolean
	link?: boolean
}

export const CloudCheckoutButton: React.FC<CloudCheckoutButtonProps> = ({
	children,
	to = CLOUD_CHECKOUT_URL,
	className,
	primary,
	secondary,
	small,
	large,
	link,
	href,
	...props
}) => {
	// Function to construct the URL with query parameters
	const getCheckoutUrl = () => {
		const piToken = window.CODE_SNIPPETS?.restAPI?.piToken || ''
		const hostUrl = window.location.origin

		const url = new URL(to)
		url.searchParams.append('pi_token', piToken)
		url.searchParams.append('host_url', hostUrl)

		return url.toString()
	}

	return (
		<a
			href={getCheckoutUrl()}
			target="_blank"
			rel="noreferrer"
			className={classnames('button', className, {
				'button-primary': primary,
				'button-secondary': secondary,
				'button-large': large,
				'button-small': small,
				'button-link': link
			})}
			{...props}
		>
			{children}
		</a>
	)
}
