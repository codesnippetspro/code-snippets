import { createInterpolateElement } from '@wordpress/element'
import React from 'react'
import { __ } from '@wordpress/i18n'
import { Modal } from '@wordpress/components'
import type { Dispatch, SetStateAction } from 'react'

export interface UpsellDialogProps {
	isOpen: boolean
	setIsOpen: Dispatch<SetStateAction<boolean>>
}

export const UpsellDialog: React.FC<UpsellDialogProps> = ({ isOpen, setIsOpen }) =>
	isOpen
		? <Modal
			title=""
			className="code-snippets-upsell-dialog"
			onRequestClose={() => {
				setIsOpen(false)
			}}
		>
			<img
				src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`}
				alt={__('Code Snippets logo', 'code-snippets')}
			/>

			<h1>
				{createInterpolateElement(
					__('Unlock all cloud sync features and many more, with <span>Code Snippets Pro</span>', 'code-snippets'),
					{ span: <span /> }
				)}
			</h1>

			<p>
				{createInterpolateElement(
					__('With Code Snippets Pro you can connect your WordPress sites to the code snippets cloud platform and be able to <strong>backup, synchronise, collaborate, and deploy</strong> your snippets from one central location.', 'code-snippets'),
					{ strong: <strong /> }
				)}
			</p>

			<a
				href="https://codesnippets.pro/pricing/"
				className="button button-primary button-large"
				rel="noreferrer" target="_blank"
			>
				{__('Explore Code Snippets Pro', 'code-snippets')}
			</a>

			<h2>{__("Here's what else you get with Pro:", 'code-snippets')}</h2>
			<ul>
				<li>{__('Create, explain and verify snippets with AI', 'code-snippets')}</li>
				<li>{__('Control when snippets run with Conditions', 'code-snippets')}</li>
				<li>{__('CSS stylesheet snippets', 'code-snippets')}</li>
				<li>{__('Minified JavaScript snippets', 'code-snippets')}</li>
				<li>{__('Editor blocks and Elementor widgets', 'code-snippets')}</li>
				<li>{__('Cloud sync and backup', 'code-snippets')}</li>
				<li>{__('Cloud share and deploy', 'code-snippets')}</li>
				<li>{__('Cloud bundles and teams', 'code-snippets')}</li>
				<li>{__('WP-CLI commands', 'code-snippets')}</li>
				<li>{__('And much more!', 'code-snippets')}</li>
			</ul>
		</Modal>
		: null
