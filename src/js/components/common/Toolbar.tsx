import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useState } from 'react'
import { isLicensed, shouldShowUpsell } from '../../utils/screen'
import { buildUrl, fetchQueryParam } from '../../utils/urls'
import { BlueprintIcon } from './icons/BlueprintIcon'
import { CommunityIcon } from './icons/CommunityIcon'
import { LibraryIcon } from './icons/LibraryIcon'
import { SettingsIcon } from './icons/SettingsIcon'
import { SnippetsIcon } from './icons/SnippetsIcon'
import { UpsellDialog } from './UpsellDialog'
import type { ReactNode } from 'react'

export const SUBPAGES = ['snippets', 'blueprints', 'cloud-community', 'cloud-library'] as const

interface UpperNavLink {
	name: string
	label: string
	url: string
	pageSlug?: string
}

const UPPER_NAV_LINKS: readonly UpperNavLink[] = [
	{
		name: 'docs',
		url: 'https://codesnippets.pro/docs',
		label: __('Docs', 'code-snippets')
	},
	{
		name: 'cloud',
		url: 'https://codesnippets.cloud/',
		label: __('Cloud Dashboard', 'code-snippets')
	},
	window.CODE_SNIPPETS?.urls.welcome && {
		name: 'welcome',
		url: window.CODE_SNIPPETS.urls.welcome,
		label: __("What's New", 'code-snippets'),
		pageSlug: 'code-snippets-welcome'
	},
	window.CODE_SNIPPETS?.urls.import && {
		name: 'import-snippets',
		url: window.CODE_SNIPPETS.urls.import,
		label: _x('Import', 'snippets', 'code-snippets'),
		pageSlug: 'import-code-snippets'
	}
].filter(function <T>(value: T | undefined | null | ''): value is T {
	return !!value
})

interface SubpageLink {
	subpage: typeof SUBPAGES[number]
	label: string
	icon: ReactNode
	pro?: boolean
}

const SUBPAGE_LINKS: readonly SubpageLink[] = [
	{
		subpage: 'snippets',
		label: __('Snippets', 'code-snippets'),
		icon: <SnippetsIcon aria-hidden="true" />
	},
	{
		subpage: 'cloud-community',
		label: __('Community Cloud', 'code-snippets'),
		icon: <CommunityIcon aria-hidden="true" />
	},
	{
		subpage: 'cloud-library',
		label: __('My Library', 'code-snippets'),
		icon: <LibraryIcon aria-hidden="true" />,
		pro: true
	},
	{
		subpage: 'blueprints',
		label: __('Blueprints', 'code-snippets'),
		icon: <BlueprintIcon />,
		pro: true
	}
]

interface UpperNavProps {
	setIsUpsellDialogOpen: (isOpen: boolean) => void
}

const UpperNavItems = () =>
	<>
		{UPPER_NAV_LINKS.map(link =>
			<li key={link.name}>
				<a
					href={link.url}
					className={classnames(`${link.name}-link`, { 'active-link': isActiveLink(link) })}
					{...!link.pageSlug && { target: '_blank', rel: 'noopener noreferrer' }}
				>
					{link.label}
				</a>
			</li>)}
	</>

const MoreNav = () =>
	<li className="toolbar-more-item">
		<details className="toolbar-more-menu">
			<summary>
				{__('More', 'code-snippets')}
				<span className="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
			</summary>
			<ul>
				<UpperNavItems />
			</ul>
		</details>
	</li>

const UpperNav: React.FC<UpperNavProps> = ({ setIsUpsellDialogOpen }) =>
	<div className="code-snippets-toolbar-upper">
		<div className="logo">
			<img
				src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`}
				alt={__('Code Snippets logo', 'code-snippets')}
				aria-hidden="true"
			/>

			<div>{__('Code Snippets', 'code-snippets')}</div>
		</div>

		<nav aria-label={__('Main links', 'code-snippets')}>
			<ul>
				<UpperNavItems />
				<MoreNav />

				{shouldShowUpsell() && (
					<li className="toolbar-upgrade-item">
						<a
							className="button button-large button-secondary"
							href="https://codesnippets.pro/pricing/"
							target="_blank" rel="noopener noreferrer"
							onClick={event => {
								event.preventDefault()
								setIsUpsellDialogOpen(true)
							}}
						>
							{__('Upgrade to Pro', 'code-snippets')}
						</a>
					</li>)}
			</ul>
		</nav>
	</div>

const currentPage = fetchQueryParam('page')
const currentSubpage = fetchQueryParam('subpage')

const isActiveLink = (link: UpperNavLink | SubpageLink): boolean => {
	if ('subpage' in link) {
		return currentSubpage === link.subpage
	}

	if ('pageSlug' in link) {
		return !currentSubpage && currentPage === link.pageSlug
	}

	return false
}

const LowerNav = () =>
	<div className="code-snippets-toolbar-lower">
		<nav aria-label={__('Main features', 'code-snippets')}>
			<ul>
				{SUBPAGE_LINKS.map(link =>
					<li key={link.subpage}>
						<a
							href={buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: link.subpage })}
							className={classnames(`${link.subpage}-link`, { 'active-link': isActiveLink(link) })}
						>
							{link.icon}
							<span className="toolbar-nav-label">{link.label}</span>
							{link.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
						</a>
					</li>)}

				{window.CODE_SNIPPETS?.urls.settings && (
					<li className="toolbar-end-item">
						<a href={window.CODE_SNIPPETS.urls.settings} className="settings-link">
							<SettingsIcon aria-hidden="true" />
							<span className="toolbar-nav-label">{__('Settings', 'code-snippets')}</span>
						</a>
					</li>)}
			</ul>
		</nav>
	</div>

export const Toolbar = () => {
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)

	return (
		<div className="code-snippets-toolbar">
			<UpperNav setIsUpsellDialogOpen={setIsUpsellDialogOpen} />
			<LowerNav />
			<UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />
		</div>
	)
}
