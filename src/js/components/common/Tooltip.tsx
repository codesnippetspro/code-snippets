import React from 'react'
import classnames from 'classnames'
import type { ReactNode } from 'react'

export interface TooltipProps {
	children: ReactNode
	invertBlock?: boolean
}

export const Tooltip: React.FC<TooltipProps> = ({ children, invertBlock }) =>
	<div className={classnames('help-tooltip', { 'invert-block': invertBlock })}>
		<span className="dashicons dashicons-editor-help"></span>
		<div className="help-tooltip-text">
			{children}
		</div>
	</div>
