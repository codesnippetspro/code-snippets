import React from 'react'
import classnames from 'classnames'
import type { ButtonHTMLAttributes } from 'react'

export interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'id' | 'name'> {
	id?: string
	name?: string
	primary?: boolean
	small?: boolean
	large?: boolean
}

export const Button: React.FC<ButtonProps> = ({
	id,
	children,
	className,
	name = 'submit',
	primary = false,
	small = false,
	large = false,
	type = 'button',
	onClick,
	...props
}) =>
	<button
		id={id ?? name}
		name={name}
		type={type}
		{...props}
		onClick={event => {
			if (onClick) {
				event.preventDefault()
				onClick(event)
			}
		}}
		className={classnames('button', className, {
			'button-primary': primary,
			'button-large': large,
			'button-small': small
		})}
	>
		{children}
	</button>
