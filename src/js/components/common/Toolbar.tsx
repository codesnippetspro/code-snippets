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
		label: __("What's New", 'code-snippets')
	}
]

const LOWER_NAV_LINKS: NavLink[] = [
	{
		name: 'snippets',
		url: window.CODE_SNIPPETS?.urls.manage,
		label: __('Snippets', 'code-snippets'),
		icon: <SnippetsIcon aria-hidden="true" />
	},
	{
		name: 'cloud-community',
		label: __('Community Cloud', 'code-snippets'),
		icon: <CommunityIcon aria-hidden="true" />
	},
	{
		name: 'cloud-library',
		label: __('My Library', 'code-snippets'),
		icon: <LibraryIcon aria-hidden="true" />,
		pro: true
	},
	{
		name: 'cloud-teams',
		label: __('My Teams', 'code-snippets'),
		icon: <TeamsIcon aria-hidden="true" />,
		pro: true
	},
	{
		name: 'settings',
		url: window.CODE_SNIPPETS?.urls.settings,
		label: __('Settings', 'code-snippets'),
		icon: <SettingsIcon aria-hidden="true" />
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
				{UPPER_NAV_LINKS.map(({ name, url, label, external }) =>
					<li key={name}>
						<a
							href={url}
							className={classnames(`${name}-link`, { 'active-link': currentPage?.endsWith(name) })}
							{...external && { target: '_blank', rel: 'noopener noreferrer' }}
						>
							{label}
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

const currentPage = fetchQueryParam('subpage') ?? fetchQueryParam('page')

const LowerNav: React.FC<NavProps> = ({ setIsUpsellDialogOpen }) =>
	<div className="code-snippets-toolbar-lower">
		<nav aria-label={__('Main features', 'code-snippets')}>
			<ul>
				{LOWER_NAV_LINKS.map(({ name, url, label, pro, icon }) =>
					<li key={name}>
						<a
							href={url ?? buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: name })}
							className={classnames(`${name}-link`, { 'active-link': currentPage?.endsWith(name) })}
							onClick={event => {
								if (pro && !isLicensed()) {
									event.preventDefault()
									setIsUpsellDialogOpen(true)
								}
							}}
						>
							{icon}
							<span>{label}</span>
							{pro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
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
			<LowerNav setIsUpsellDialogOpen={setIsUpsellDialogOpen} />
			<UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />
		</div>
	)
}
