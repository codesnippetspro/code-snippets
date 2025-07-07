import { Modal } from '@wordpress/components'
import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Button, ButtonProps } from '../../common/Button'
import { Snippet } from '../../../types/Snippet'
import { isLicensed } from '../../../utils/general'

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

export interface CloudAIButtonProps extends ButtonProps {
	snippet: Snippet
}

export const CloudAIButton: React.FC<CloudAIButtonProps> = ({
	snippet,
	children,
	onClick,
	disabled,
	...props
}) => {
	const [isCloudModalOpen, setIsCloudModalOpen] = useState(false)

	return isLicensed() ?
		<>
			{isCloudModalOpen ?
				<Modal
					icon={<GenerateIcon />}
					title={__('Missing Cloud Connection', 'code-snippets')}
					onRequestClose={() => setIsCloudModalOpen(false)}
					className="cloud-connect-modal"
				>
					<div className="icons-group">
						<span className="dashicons dashicons-admin-home"></span>
						<span className="dashicons dashicons-no"></span>
						<span className="dashicons dashicons-cloud"></span>
					</div>

					<p>{__('A connection to Code Snippets Cloud is required to use this functionality.', 'code-snippets')}</p>
					<p>{__('Once connected, reload this page to recognise the new connection status.', 'code-snippets')}</p>

					<div className="action-buttons">
						<a
							className="components-button is-primary"
							href={window.CODE_SNIPPETS?.urls.connectCloud}
							target="_blank" rel="noreferrer"
						>
							{__('Connect and Authorise', 'code-snippets')}
						</a>
					</div>
				</Modal> :
				null}

			<Button small {...props} onClick={event => {
				if (window.CODE_SNIPPETS?.isCloudConnected) {
					onClick?.(event)
				} else {
					setIsCloudModalOpen(true)
				}
			}}>
				<GenerateIcon />
				{' '}{children}{' '}
				<span className="badge">beta</span>
			</Button>
		</> :
		null
}
