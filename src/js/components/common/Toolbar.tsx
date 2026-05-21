import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useState } from 'react'
import { isLicensed, shouldShowUpsell } from '../../utils/screen'
import { buildUrl, fetchQueryParam } from '../../utils/urls'
import { CommunityIcon, LibraryIcon, SettingsIcon, SnippetsIcon, TeamsIcon } from './icons/ToolbarIcons'
import { UpsellDialog } from './UpsellDialog'
import type { ReactNode } from 'react'

interface NavLink {
	name: string
	url?: string
	label: string
	external?: boolean
	icon?: ReactNode
	pro?: boolean
	pageSlug?: string
	subpage?: string
}

const UPPER_NAV_LINKS: NavLink[] = [
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
	}
]

const LOWER_NAV_LINKS: NavLink[] = [
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
		name: 'cloud-teams',
		label: __('My Teams', 'code-snippets'),
		icon: <TeamsIcon aria-hidden="true" />,
		pro: true,
		subpage: 'cloud-teams'
	},
	{
		name: 'settings',
		url: window.CODE_SNIPPETS?.urls.settings,
		label: __('Settings', 'code-snippets'),
		icon: <SettingsIcon aria-hidden="true" />,
		pageSlug: 'snippets-settings'
	}
]

interface NavProps {
	setIsUpsellDialogOpen: (isOpen: boolean) => void
}

const UpperNav: React.FC<NavProps> = ({ setIsUpsellDialogOpen }) =>
	<div className="code-snippets-toolbar-upper">
		<div className="logo">
			<img
				src={`${window.CODE_SNIPPETS?.urls.plugin}/assets/icon.svg`}
				alt={__('Code Snippets logo', 'code-snippets')}
				aria-hidden="true"
			/>

			<h1>{__('Code Snippets', 'code-snippets')}</h1>
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
					</li>
				)}
				{shouldShowUpsell()
					? <li>
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
					</li>
					: null}
			</ul>
		</nav>
	</div>

const currentPage = fetchQueryParam('page')
const currentSubpage = fetchQueryParam('subpage')

const isActiveLink = ({ pageSlug, subpage }: NavLink): boolean => {
	if (subpage) {
		return currentSubpage === subpage
	}
	if (pageSlug) {
		return !currentSubpage && currentPage === pageSlug
	}
	return false
}

const LowerNav: React.FC<NavProps> = ({ setIsUpsellDialogOpen }) =>
	<div className="code-snippets-toolbar-lower">
		<nav aria-label={__('Main features', 'code-snippets')}>
			<ul>
				{LOWER_NAV_LINKS.map(link =>
					<li key={link.name}>
						<a
							href={link.url ?? buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: link.name })}
							className={classnames(`${link.name}-link`, { 'active-link': isActiveLink(link) })}
							onClick={event => {
								if (link.pro && !isLicensed()) {
									event.preventDefault()
									setIsUpsellDialogOpen(true)
								}
							}}
						>
							{link.icon}
							<span>{link.label}</span>
							{link.pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
						</a>
					</li>)}
			</ul>
		</nav>
	</div>

export const Toolbar = () => {
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)

	return (
		<>
			<div className="code-snippets-toolbar">
				<UpperNav setIsUpsellDialogOpen={setIsUpsellDialogOpen} />
				<LowerNav setIsUpsellDialogOpen={setIsUpsellDialogOpen} />
				<UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />
			</div>
			<hr className="wp-header-end" />
		</>
	)
}
