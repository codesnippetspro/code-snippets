import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { isLicensed } from '../../utils/screen'
import { buildUrl, updateQueryParams } from '../../utils/urls'
import { UpsellDialog } from './UpsellDialog'

export interface AdminTabsProps<T extends string> {
	tabs: readonly T[]
	proTabs?: T[]
	ariaLabel: string
	className?: classnames.Argument
	tabLabels: Record<T, string>
	currentTab: T
	queryArgName: string
	setCurrentTab: (tab: T) => void
}

export const AdminTabs = <T extends string>({
	tabs,
	proTabs,
	ariaLabel,
	tabLabels,
	className,
	currentTab,
	queryArgName,
	setCurrentTab
}: AdminTabsProps<T>) => {
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)

	return (
		<>
			<nav
				className={classnames('nav-tab-wrapper', 'snippets-admin-tabs', className)}
				aria-label={ariaLabel}
			>
				{tabs.map(tab =>
					<a
						key={tab}
						href={buildUrl(window.location.href, { [queryArgName]: tab })}
						className={classnames('nav-tab', `${tab}-tab`, { 'nav-tab-active': tab === currentTab })}
						aria-current={tab === currentTab ? 'page' : undefined}
						onClick={event => {
							event.preventDefault()

							if (proTabs?.includes(tab) && !isLicensed()) {
								setIsUpsellDialogOpen(true)
							} else {
								updateQueryParams({ [queryArgName]: tab })
								setCurrentTab(tab)
							}
						}}
					>
						{tabLabels[tab]}
						{proTabs?.includes(tab) && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
					</a>)}
			</nav>

			{proTabs && <UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />}
		</>
	)
}
