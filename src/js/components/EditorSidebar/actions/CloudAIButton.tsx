import { Modal } from '@wordpress/components'
import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { isCondition } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { isLicensed } from '../../../utils/screen'
import { GenerateIcon } from '../../common/icons/GenerateIcon'
import type { ButtonProps } from '../../common/Button'
import type { Snippet } from '../../../types/Snippet'

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

	return isLicensed() && !isCondition(snippet)
		? <>
			{isCloudModalOpen
				? <Modal
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
				</Modal>
				: null}

			<Button small {...props} onClick={event => {
				if (window.CODE_SNIPPETS?.isCloudConnected) {
					onClick?.(event)
				} else {
					setIsCloudModalOpen(true)
				}
			}}>
				<GenerateIcon />
				{' '}{children}
			</Button>
		</>
		: null
}
