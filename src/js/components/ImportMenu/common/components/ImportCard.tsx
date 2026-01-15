import React, { forwardRef } from 'react'
import classnames from 'classnames'
import type { CSSProperties , HTMLAttributes, ReactNode} from 'react'

export interface ImportCardProps extends Omit<HTMLAttributes<HTMLDivElement>, 'className'> {
	children: ReactNode
	className?: string
	variant?: 'default' | 'controls'
}

export const ImportCard = forwardRef<HTMLDivElement, ImportCardProps>(({
	children,
	className,
	variant = 'default',
	style,
	...props
}, ref) => {
	const cardStyle: CSSProperties = {
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
					'import-controls': 'controls' === variant
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
