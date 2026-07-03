import React, { useId } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { trimTrailingChar } from '../../utils/text'
import type { ReactNode } from 'react'

export interface TooltipProps {
	block?: boolean
	inline?: boolean
	start?: boolean
	end?: boolean
	icon?: ReactNode
	children: ReactNode
	className?: classnames.Argument
	label?: string
}

export const Tooltip: React.FC<TooltipProps> = ({
	block, inline, start, end,
	icon,
	className,
	children,
	label
}) => {
	const tooltipId = useId()
	return (
		<div className={classnames(
			'tooltip help-tooltip',
			{ 'tooltip-block': block, 'tooltip-inline': inline, 'tooltip-start': start, 'tooltip-end': end },
			className
		)}>
			<button
				type="button"
				className="tooltip-trigger"
				aria-label={label ?? __('More information', 'code-snippets')}
				aria-describedby={tooltipId}
			>
				{icon ?? <span className="dashicons dashicons-editor-help" aria-hidden="true"></span>}
			</button>
			<div role="tooltip" className="tooltip-content" id={tooltipId}>
				{children}
			</div>
		</div>
	)
}

export const ErrorTooltip: React.FC<{ message: string }> = ({ message }) =>
	<Tooltip
		block
		end
		label={__('Error', 'code-snippets')}
		icon={<span className="dashicons dashicons-warning" aria-hidden="true"></span>}
	>
		{`${trimTrailingChar(message, '.!?')}. ${__('Please try again.', 'code-snippets')}`}
	</Tooltip>
