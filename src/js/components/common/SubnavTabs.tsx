import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import React, { useMemo, useState } from 'react'
import { isLicensed } from '../../utils/screen'
import { UpsellDialog } from './UpsellDialog'
import type { ReactNode } from 'react'

export interface SubnavTab<T extends string> {
	name: T
	label: string
	icon?: ReactNode
	pro?: boolean
	count?: number
}

export interface SubnavTabsProps<T extends string> {
	tabs: readonly SubnavTab<T>[]
	ariaLabel: string
	className?: string
	currentTab: T
	setCurrentTab: (tab: T) => void
	endContent?: ReactNode
}

/**
 * Toolbar-style subnavigation bar shared across manage pages: tab buttons
 * with an icon beside the label, pro-gated tabs showing an upsell chip and
 * dialog for unlicensed users, and an optional end slot for extra items
 * such as the card/table view toggle. Uses the same design language as the
 * snippet-type nav on the manage snippets page.
 */
export const SubnavTabs = <T extends string>({
	tabs,
	ariaLabel,
	className,
	currentTab,
	setCurrentTab,
	endContent
}: SubnavTabsProps<T>) => {
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)
	const hasProTabs = useMemo(() => !isLicensed() && tabs.some(tab => tab.pro), [tabs])

	return (
		<>
			<nav className={classnames('snippet-type-nav', className)} aria-label={ariaLabel}>
				<ul>
					{tabs.map(tab =>
						<li key={tab.name}>
							<button
								type="button"
								className={classnames('snippet-type-link', `${tab.name}-subnav-link`, {
									'active-type': tab.name === currentTab
								})}
								aria-current={tab.name === currentTab ? 'page' : undefined}
								onClick={() => {
									if (tab.pro && !isLicensed()) {
										setIsUpsellDialogOpen(true)
									} else {
										setCurrentTab(tab.name)
									}
								}}
							>
								<span>{tab.label}</span>
								{undefined !== tab.count && <span className="subnav-count">{tab.count}</span>}
								{tab.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
							</button>
						</li>)}

					{endContent}
				</ul>
			</nav>

			{hasProTabs && <UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />}
		</>
	)
}
