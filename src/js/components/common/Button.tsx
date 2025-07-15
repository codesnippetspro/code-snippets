import React from 'react'
import classnames from 'classnames'
import type { ButtonHTMLAttributes } from 'react'

export interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'id' | 'name'> {
	id?: string
	name?: string
	primary?: boolean
	secondary?: boolean
	small?: boolean
	large?: boolean
	link?: boolean
}

export const Button: React.FC<ButtonProps> = ({
	id,
	children,
	className,
	name,
	primary = false,
	secondary = false,
	small = false,
	large = false,
	link = false,
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
			'button-secondary': secondary,
			'button-large': large,
			'button-small': small,
			'button-link': link
		})}
	>
		{children}
	</button>
