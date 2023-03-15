import React, { ButtonHTMLAttributes } from 'react'
import classnames from 'classnames'

export interface ActionButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'id' | 'name'> {
	id?: string
	name?: string
	primary?: boolean
	small?: boolean
	large?: boolean
	text: string
}

export const ActionButton: React.FC<ActionButtonProps> = ({
	id,
	text,
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
		className={classnames('button', {
			'button-primary': primary,
			'button-large': large,
			'button-small': small
		})}
	>{text}</button>
