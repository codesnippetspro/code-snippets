import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import React from 'react'
import { isLicensed } from '../../utils/screen'
import { fetchQueryParam, updateQueryParams } from '../../utils/urls'

export interface SubnavTab {
	name: string
	label: string
	pro?: boolean
}

export const getTabFromQuery = <Tab extends SubnavTab>(
	tabs: Tab[],
	queryParamName: string
): Tab => {
	const tabParam = fetchQueryParam(queryParamName)

	for (const tab of tabs) {
		if (tab.name === tabParam) {
			return tab
		}
	}

	return tabs[0]
}

export interface SubnavTabsProps<Tab extends SubnavTab> {
	tabs: readonly Tab[]
	ariaLabel: string
	className?: string
	currentTab: Tab
	getTabCount?: (tab: Tab) => number | undefined
	setCurrentTab: (tab: Tab) => void
	queryParamName?: string
}

export const SubnavTabs = <Tab extends SubnavTab>({
	tabs,
	ariaLabel,
	className,
	currentTab,
	getTabCount,
	setCurrentTab,
	queryParamName,
}: SubnavTabsProps<Tab>) =>
	<nav className={classnames('snippet-type-nav', className)} aria-label={ariaLabel}>
		<ul>
			{tabs.map(tab => {
				const count = getTabCount?.(tab)

				return (
					<li key={tab.name}>
						<button
							type="button"
							className={classnames('snippet-type-link', `${tab.name}-subnav-link`, {
								'active-type': tab.name === currentTab.name
							})}
							aria-current={tab.name === currentTab.name ? 'page' : undefined}
							onClick={() => {
								setCurrentTab(tab)

								if (queryParamName) {
									updateQueryParams({ [queryParamName]: tab.name })
								}
							}}
						>
							<span>{tab.label}</span>
							{count && <span className="subnav-count">{count}</span>}
							{tab.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
						</button>
					</li>
				)
			})}
		</ul>
	</nav>
