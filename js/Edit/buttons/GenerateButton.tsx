import React from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { Button, ButtonProps } from '../../common/Button'
import { Snippet } from '../../types/Snippet'
import { getSnippetType, isLicensed } from '../../utils/snippets'

export const GenerateIcon = () =>
	<svg
		viewBox="0 0 105.23233 130.03937"
		version="1.1"
		xmlns="http://www.w3.org/2000/svg"
	>
		<g transform="translate(-51.837226,-83.480318)">
			<path
				d="m 4.7995,2.22417 c 0,0 -0.111665,-0.573665 -0.685335,-0.685335 C 4.68783,1.42717 4.7995,0.8535 4.7995,0.8535 c 0.111665,0.573665 0.685335,0.685335 0.685335,0.685335 0,0 -0.573669,0.111335 -0.685335,0.685335 z"
				transform="matrix(25.4,0,0,25.4,17.754744,61.801418)"
				fill="currentColor"
			/>
			<path
				d="m 2.94783,4.99616 c 0,0 -0.261665,-1.34433 -1.606,-1.606 1.34433,-0.261665 1.606,-1.606 1.606,-1.606 0.261665,1.34433 1.606,1.606 1.606,1.606 0,0 -1.34433,0.261665 -1.606,1.606 z"
				transform="matrix(25.4,0,0,25.4,17.754744,61.801418)"
				fill="currentColor"
			/>
			<path
				d="m 4.55383,5.97316 c 0,0 -0.111665,-0.573665 -0.685331,-0.685335 C 4.44216,5.17616 4.55383,4.60249 4.55383,4.60249 c 0.111665,0.573665 0.685335,0.685335 0.685335,0.685335 0,0 -0.573669,0.111669 -0.685335,0.685335 z"
				transform="matrix(25.4,0,0,25.4,17.754744,61.801418)"
				fill="currentColor"
			/>
		</g>
	</svg>

export interface GenerateButtonProps extends ButtonProps {
	snippet: Snippet
}

export const GenerateButton: React.FC<GenerateButtonProps> = ({
	snippet,
	className,
	children,
	...props
}) =>
	'php' === getSnippetType(snippet) && isLicensed() ?
		<Button small className={classnames('generate-button', className)} {...props}>
			<GenerateIcon />
			{children ?? __('Generate', 'code-snippets')}
		</Button> :
		null
