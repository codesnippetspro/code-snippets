import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { ButtonHTMLAttributes } from 'react'
import { ConditionGroups } from '../../types/Condition'

export interface AddButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
	group: keyof ConditionGroups
	insertLabel: string
}

export const AddButton: React.FC<AddButtonProps> = ({ insertLabel, onClick }) =>
	<button
		className="button condition-add-button"
		onClick={event => {
			event.preventDefault()
			onClick?.(event)
		}}
	>
		<span className="dashicons dashicons-insert"></span>
		<span>{insertLabel}</span>
	</button>

export const AddGroupButton: React.FC<ButtonHTMLAttributes<HTMLButtonElement>> = ({ onClick, ...props }) =>
	<button
		{...props}
		className="button condition-add-button condition-add-group-button"
		onClick={event => {
			event.preventDefault()
			onClick?.(event)
		}}
	>
		<span className="dashicons dashicons-insert"></span>
		<span>{__('Add condition group', 'code-snippets')}</span>
	</button>

export const AddConditionButton: React.FC<ButtonHTMLAttributes<HTMLButtonElement>> = ({ className, onClick, ...props }) =>
	<div className="condition-add-row-button">
		<button
			{...props}
			className={classnames('button condition-add-button', className)}
			onClick={event => {
				event.preventDefault()
				onClick?.(event)
			}}
		>
			<span className="dashicons dashicons-insert"></span>
			<span>{__('AND', 'code-snippets')}</span>
		</button>
	</div>
