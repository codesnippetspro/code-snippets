import React, { forwardRef } from 'react'
import classnames from 'classnames'
import type { HTMLAttributes, ReactNode } from 'react'

export interface ImportCardProps extends Omit<HTMLAttributes<HTMLDivElement>, 'className'> {
	children: ReactNode
	className?: string
	variant?: 'default' | 'controls'
}

export const ImportCard = forwardRef<HTMLDivElement, ImportCardProps>(({
	children,
	className,
	variant = 'default',
	...props
}, ref) =>
	<div
		ref={ref}
		className={classnames(
			'import-snippets-card',
			{ 'import-controls': 'controls' === variant },
			className
		)}
		{...props}
	>
		{children}
	</div>)

ImportCard.displayName = 'ImportCard'
