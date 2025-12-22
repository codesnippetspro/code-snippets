import React from 'react'
import classnames from 'classnames'
import type { HTMLAttributes } from 'react'

export interface ImportCardProps extends Omit<HTMLAttributes<HTMLDivElement>, 'className'> {
	children: React.ReactNode
	className?: string
	variant?: 'default' | 'controls'
}

export const ImportCard = React.forwardRef<HTMLDivElement, ImportCardProps>(({
	children,
	className,
	variant = 'default',
	style,
	...props
}, ref) => {
	const cardStyle: React.CSSProperties = {
		backgroundColor: '#ffffff',
		padding: '25px',
		borderRadius: '5px',
		border: '1px solid #e0e0e0',
		marginBottom: '10px',
		width: '100%',
		...style
	}

	return (
		<div
			ref={ref}
			className={classnames(
				{
					'import-controls': variant === 'controls'
				},
				className
			)}
			style={cardStyle}
			{...props}
		>
			{children}
		</div>
	)
})

ImportCard.displayName = 'ImportCard'
