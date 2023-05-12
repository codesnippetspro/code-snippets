import React, { Dispatch, SetStateAction, useState } from 'react'
import { ExternalLink, Modal } from '@wordpress/components'
import { __, _n, sprintf } from '@wordpress/i18n'

export interface UpgradeDialogProps {
	isOpen: boolean
	setIsOpen: Dispatch<SetStateAction<boolean>>
}

const SMALL_PLAN_SITES = '2'
const MID_PLAN_SITES = '6'
const LARGE_PLAN_SITES = '200'

const upgradePlanCosts: Record<string, number> = {
	[SMALL_PLAN_SITES]: 39,
	[MID_PLAN_SITES]: 69,
	[LARGE_PLAN_SITES]: 119
}

export const UpgradeDialog: React.FC<UpgradeDialogProps> = ({ isOpen, setIsOpen }) => {
	const [currentPlan, setCurrentPlan] = useState(MID_PLAN_SITES)

	return isOpen ?
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

			<p className="upgrade-plans">
				{Object.keys(upgradePlanCosts).map(planSites =>
					<label key={`${planSites}-sites`}>
						<input
							type="radio"
							checked={planSites === currentPlan.toString()}
							onClick={() => setCurrentPlan(planSites)}
						/>
						{' '}
						{sprintf(_n('%d site', '%d sites', Number(planSites), 'code-snippets'), planSites)}
					</label>
				)}
			</p>

			<p className="action-buttons">
				<span className="current-plan-cost">
					{sprintf(__('$%s per year', 'code-snippets'), upgradePlanCosts[currentPlan])}
				</span>

				<ExternalLink
					className="button button-primary button-large"
					href={`https://checkout.freemius.com/mode/dialog/plugin/10565/plan/17873/licenses/${currentPlan}/`}
				>
					{__('Upgrade Now', 'code-snippets')}
				</ExternalLink>
			</p>

		</Modal> :
		null
}
