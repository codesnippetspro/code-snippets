import React from 'react'
import type { HTMLAttributes } from 'react'

export interface ImportSectionProps extends Omit<HTMLAttributes<HTMLDivElement>, 'style'> {
	children: React.ReactNode
	active?: boolean
	className?: string
	style?: React.CSSProperties
}

export const ImportSection: React.FC<ImportSectionProps> = ({
	children,
	active = false,
	className,
	style,
	...props
}) => {
	const sectionStyle: React.CSSProperties = {
		display: active ? 'block' : 'none',
		paddingTop: 0,
		...style
	}

	return (
		<div
			className={className}
			style={sectionStyle}
			{...props}
		>
			{children}
		</div>
	)
}
