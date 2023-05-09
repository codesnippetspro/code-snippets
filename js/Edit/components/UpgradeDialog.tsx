import React, { Dispatch, SetStateAction } from 'react'
import { ExternalLink, Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

export interface UpgradeDialogProps {
	isOpen: boolean
	setIsOpen: Dispatch<SetStateAction<boolean>>
}

export const UpgradeDialog: React.FC<UpgradeDialogProps> = ({ isOpen, setIsOpen }) =>
	isOpen ?
		<Modal
			title=""
			onRequestClose={() => setIsOpen(false)}
			className="code-snippets-upgrade-dialog"
		>
			<h1 className="logo">
				<img src={`${window.CODE_SNIPPETS?.pluginUrl}/assets/icon.svg`} alt="" />
				{__('Code Snippets Pro', 'code-snippets')}
			</h1>
			<p>{__('You are using the free version of Code Snippets.', 'code-snippets')}</p>
			<p>{__('Upgrade to Code Snippets Pro to unlock amazing features, including:', 'code-snippets')}
				<ul>
					<li>{__('CSS stylesheet snippets', 'code-snippets')}</li>
					<li>{__('JavaScript snippets', 'code-snippets')}</li>
					<li>{__('Specialised Elementor widgets', 'code-snippets')}</li>
					<li>{__('Integration with block editor', 'code-snippets')}</li>
					<li>{__('WP-CLI snippet commands', 'code-snippets')}</li>
				</ul>
				{__('… and more!', 'code-snippets')}
			</p>

			<ExternalLink
				className="button button-primary button-large"
				href={__('https://codesnippets.pro/pricing', 'code-snippets')}
			>
				{__('Upgrade Now', 'code-snippets')}
			</ExternalLink>

		</Modal> :
		null
