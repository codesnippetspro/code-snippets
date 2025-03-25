import React from "react"
import { ReactNode } from "react"

export interface TooltipProps {
	children: ReactNode
}

export const Tooltip: React.FC<TooltipProps> = ({ children }) =>
	<div className="help-tooltip">
		<span className="dashicons dashicons-editor-help"></span>
		<div className="help-tooltip-text">
			{children}
		</div>
	</div>
