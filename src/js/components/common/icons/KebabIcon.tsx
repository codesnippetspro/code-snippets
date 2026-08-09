import React from 'react'
import type { SVGProps } from 'react'

export const KebabIcon: React.FC<SVGProps<SVGSVGElement>> = props =>
	<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" {...props}>
		<circle cx="9" cy="3.5" r="1.6" fill="currentColor" />
		<circle cx="9" cy="9" r="1.6" fill="currentColor" />
		<circle cx="9" cy="14.5" r="1.6" fill="currentColor" />
	</svg>
