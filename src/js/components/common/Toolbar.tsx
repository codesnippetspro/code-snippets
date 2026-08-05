import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useState } from 'react'
import { isLicensed, shouldShowUpsell } from '../../utils/screen'
import { buildUrl, fetchQueryParam } from '../../utils/urls'
import { BlueprintIcon, CommunityIcon, LibraryIcon, SettingsIcon, SnippetsIcon } from './icons/ToolbarIcons'
import { UpsellDialog } from './UpsellDialog'
import type { ReactNode } from 'react'

export const SUBPAGES = ['snippets', 'blueprints', 'cloud-community', 'cloud-library'] as const

interface NavLink {
	name: string
	url?: string
	label: string
	external?: boolean
	icon?: ReactNode
	pro?: boolean
	pageSlug?: string
	subpage?: typeof SUBPAGES[number]
	end?: boolean
}

const UPPER_NAV_LINKS: readonly NavLink[] = [
	{
		name: 'docs',
		url: 'https://codesnippets.pro/docs',
		label: __('Docs', 'code-snippets'),
		external: true
	},
	{
		name: 'cloud',
		url: 'https://codesnippets.cloud/',
		label: __('Cloud Dashboard', 'code-snippets'),
		external: true
	},
	{
		name: 'welcome',
		url: window.CODE_SNIPPETS?.urls.welcome,
		label: __("What's New", 'code-snippets'),
		pageSlug: 'code-snippets-welcome'
	},
	{
		name: 'import-snippets',
		url: window.CODE_SNIPPETS?.urls.import,
		label: _x('Import', 'snippets', 'code-snippets'),
		pageSlug: 'import-code-snippets'
	}
]

const LOWER_NAV_LINKS: readonly NavLink[] = [
	{
		name: 'snippets',
		url: window.CODE_SNIPPETS?.urls.manage,
		label: __('Snippets', 'code-snippets'),
		icon: <SnippetsIcon aria-hidden="true" />,
		pageSlug: 'snippets'
	},
	{
		name: 'cloud-community',
		label: __('Community Cloud', 'code-snippets'),
		icon: <CommunityIcon aria-hidden="true" />,
		subpage: 'cloud-community'
	},
	{
		name: 'cloud-library',
		label: __('My Library', 'code-snippets'),
		icon: <LibraryIcon aria-hidden="true" />,
		pro: true,
		subpage: 'cloud-library'
	},
	{
		name: 'blueprints',
		label: __('Blueprints', 'code-snippets'),
		icon: <BlueprintIcon />,
		pro: true,
		subpage: 'blueprints'
	},
	{
		name: 'settings',
		url: window.CODE_SNIPPETS?.urls.settings,
		label: __('Settings', 'code-snippets'),
		icon: <SettingsIcon aria-hidden="true" />,
		pageSlug: 'snippets-settings',
		end: true
	}
]

interface UpperNavProps {
	setIsUpsellDialogOpen: (isOpen: boolean) => void
}

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
				{UPPER_NAV_LINKS.map(link =>
					<li key={link.name}>
						<a
							href={link.url}
							className={classnames(`${link.name}-link`, { 'active-link': isActiveLink(link) })}
							{...link.external && { target: '_blank', rel: 'noopener noreferrer' }}
						>
							{link.label}
						</a>
					</li>)}

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

const isActiveLink = ({ pageSlug, subpage }: Readonly<NavLink>): boolean => {
	if (subpage) {
		return currentSubpage === subpage
	}
	if (pageSlug) {
		return !currentSubpage && currentPage === pageSlug
	}
	return false
}

const LowerNav = () =>
	<div className="code-snippets-toolbar-lower">
		<nav aria-label={__('Main features', 'code-snippets')}>
			<ul>
				{LOWER_NAV_LINKS.map(link =>
					<li key={link.name} className={link.end ? 'toolbar-end-item' : undefined}>
						<a
							href={link.url ?? buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: link.name })}
							className={classnames(`${link.name}-link`, { 'active-link': isActiveLink(link) })}
						>
							{link.icon}
							<span className="toolbar-nav-label">{link.label}</span>
							{link.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
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
