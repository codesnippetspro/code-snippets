import React from 'react'
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
}

export const Tooltip: React.FC<TooltipProps> = ({ block, inline, start, end, icon, className, children }) =>
	<div className={classnames(
		'tooltip',
		{ 'tooltip-block': block, 'tooltip-inline': inline, 'tooltip-start': start, 'tooltip-end': end },
		className
	)}>
		{icon ?? <span className="dashicons dashicons-editor-help"></span>}
		<div className="tooltip-content">
			{children}
		</div>
	</div>

export const ErrorTooltip: React.FC<{ message: string }> = ({ message }) =>
	<Tooltip block end icon={<span className="dashicons dashicons-warning"></span>}>
		{`${trimTrailingChar(message, '.!?')}. ${__('Please try again.', 'code-snippets')}`}
	</Tooltip>
