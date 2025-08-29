import React from 'react'
import classnames from 'classnames'
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
