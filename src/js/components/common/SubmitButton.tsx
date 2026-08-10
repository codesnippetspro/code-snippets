import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { InputHTMLAttributes } from 'react'

export interface SubmitButtonProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'name' | 'value'> {
	id?: string
	name?: string
	primary?: boolean
	secondary?: boolean
	small?: boolean
	large?: boolean
	wrap?: boolean
	text?: string
}

export const SubmitButton: React.FC<SubmitButtonProps> = ({
	id,
	text,
	name = 'submit',
	primary,
	secondary,
	small,
	large,
	wrap,
	className,
	...inputProps
}) => {
	const button =
		<input
			id={id ?? name}
			type="submit"
			name={name}
			value={text ?? __('Save Changes', 'code-snippets')}
			className={classnames(
				'button',
				{
					'button-primary': primary,
					'button-secondary': secondary,
					'button-small': small,
					'button-large': large
				},
				className
			)}
			{...inputProps}
		/>

	return wrap ? <p className="submit">{button}</p> : button
}
