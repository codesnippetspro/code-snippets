import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import React, { useMemo, useState } from 'react'
import { isLicensed } from '../../utils/screen'
import { UpsellDialog } from './UpsellDialog'

export interface SubnavTab<T extends string> {
	name: T
	label: string
	pro?: boolean
	count?: number
}

export interface SubnavTabsProps<T extends string> {
	tabs: readonly SubnavTab<T>[]
	ariaLabel: string
	className?: string
	currentTab: T
	setCurrentTab: (tab: T) => void
}

export const SubnavTabs = <T extends string>({
	tabs,
	ariaLabel,
	className,
	currentTab,
	setCurrentTab,
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
								{tab.count ? <span className="subnav-count">{tab.count}</span> : null}
								{tab.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
							</button>
						</li>)}
				</ul>
			</nav>

			{hasProTabs && <UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />}
		</>
	)
}
