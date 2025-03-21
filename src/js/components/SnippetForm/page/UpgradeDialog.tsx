import React, { useState } from 'react'
import { ExternalLink, Modal } from '@wordpress/components'
import { __, _n, sprintf } from '@wordpress/i18n'
import type { Dispatch, SetStateAction } from 'react'

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

const UpgradeDialogPlans = () => {
	const [currentPlan, setCurrentPlan] = useState(MID_PLAN_SITES)

	return (
		<>
			<p><strong>{__('How many websites do you plan to use Code Snippets on?', 'code-snippets')}</strong></p>
			<p>{__('We offer three distinct plans, each tailored to meet your needs.', 'code-snippets')}</p>

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
		</>
	)
}

interface UpgradeInfoProps {
	nextTab: VoidFunction
}

const UpgradeInfo: React.FC<UpgradeInfoProps> = ({ nextTab }) =>
	<>
		<p>
			{__('You are using the free version of Code Snippets.', 'code-snippets')}{' '}
			{__('Upgrade to Code Snippets Pro to unleash its full potential:', 'code-snippets')}
			<ul>
				<li>
					<strong>{__('CSS stylesheet snippets: ', 'code-snippets')}</strong>
					{__('Craft impeccable websites with advanced CSS snippets.', 'code-snippets')}
				</li>
				<li>
					<strong>{__('JavaScript snippets: ', 'code-snippets')}</strong>
					{__('Enhance user interaction with the power of JavaScript.', 'code-snippets')}
				</li>
				<li>
					<strong>{__('Specialized Elementor widgets: ', 'code-snippets')}</strong>
					{__('Easily customize your site with Elementor widgets.', 'code-snippets')}
				</li>
				<li>
					<strong>{__('Integration with block editor: ', 'code-snippets')}</strong>
					{__('Seamlessly incorporate your snippets within the block editor.', 'code-snippets')}
				</li>
				<li>
					<strong>{__('WP-CLI snippet commands: ', 'code-snippets')}</strong>
					{__('Access and control your snippets directly from the command line.', 'code-snippets')}
				</li>
				<li>
					<strong>{__('Premium support: ', 'code-snippets')}</strong>
					{__("Direct access to our team. We're happy to help!", 'code-snippets')}
				</li>
			</ul>

			{__('…and so much more!', 'code-snippets')}
		</p>

		<p className="action-buttons">
			<ExternalLink
				className="button button-secondary"
				href="https://codesnippets.pro/pricing/"
			>
				{__('Learn More', 'code-snippets')}
			</ExternalLink>

			<button
				className="button button-primary button-large"
				onClick={nextTab}
			>
				{__('See Plans', 'code-snippets')}
				<span className="dashicons dashicons-arrow-right"></span>
			</button>
		</p>
	</>

export const UpgradeDialog: React.FC<UpgradeDialogProps> = ({ isOpen, setIsOpen }) => {
	const [currentTab, setCurrentTab] = useState(0)

	return isOpen
		? <Modal
			title=""
			className="code-snippets-upgrade-dialog"
			onRequestClose={() => {
				setIsOpen(false)
				setCurrentTab(0)
			}}
		>
			<h1 className="logo">
				<img src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`} alt="" />
				{__('Code Snippets Pro', 'code-snippets')}
			</h1>

			{0 === currentTab
				? <UpgradeInfo nextTab={() => setCurrentTab(1)} />
				: <UpgradeDialogPlans />
			}

		</Modal>
		: null
}
