import classnames from 'classnames'
import React from 'react'
import { Button } from './Button'
import type { ReactNode } from 'react'
import type { ButtonProps } from './Button'

export interface TooltipButtonProps extends ButtonProps {
	tooltip: ReactNode
	block?: boolean
	inline?: boolean
	start?: boolean
	end?: boolean
	containerClassName?: string
}

export const TooltipButton: React.FC<TooltipButtonProps> = ({
	block, inline, start, end,
	tooltip,
	disabled,
	children,
	containerClassName,
	...buttonProps
}) =>
	<div className={classnames(
		containerClassName,
		!disabled && { 'tooltip-block': block, 'tooltip-inline': inline, 'tooltip-start': start, 'tooltip-end': end }
	)}>
		<Button {...buttonProps} disabled={disabled}>
			{children}
			{!disabled && <div role="tooltip" className="tooltip-content">{tooltip}</div>}
		</Button>
	</div>
