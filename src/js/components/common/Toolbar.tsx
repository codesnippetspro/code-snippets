import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { isLicensed, shouldShowUpsell } from '../../utils/screen'
import { buildUrl } from '../../utils/urls'
import { AiAgentIcon } from './icons/AiAgentIcon'
import { BlueprintIcon } from './icons/BlueprintIcon'
import { CommunityIcon } from './icons/CommunityIcon'
import { LibraryIcon } from './icons/LibraryIcon'
import { SettingsIcon } from './icons/SettingsIcon'
import { SnippetsIcon } from './icons/SnippetsIcon'
import { UpsellDialog } from './UpsellDialog'
import type { SVGProps } from 'react'

export const SUBPAGES = ['snippets', 'blueprints', 'cloud-community', 'cloud-library', 'ai-agent'] as const

const searchParams = new URLSearchParams(window.location.search)

interface UpperNavItemProps {
	name: string
	url: string
	label: string
}

const UpperNavItem = ({ name, url, label }: UpperNavItemProps) => {
	const pageSlug = useMemo(() => new URL(url).searchParams.get('page'), [url])

	const isActive = !searchParams.get('subpage') && searchParams.get('page') === pageSlug

	return (
		<li>
			<a
				href={url}
				className={classnames(`${name}-link`, { 'active-link': isActive })}
				{...!pageSlug && { target: '_blank', rel: 'noopener noreferrer' }}
			>
				{label}
			</a>
		</li>
	)
}
const UpperNavItems = () =>
	<>
		<UpperNavItem
			name="docs"
			url="https://codesnippets.pro/docs"
			label={__('Docs', 'code-snippets')}
		/>

		<UpperNavItem
			name="cloud"
			url="https://codesnippets.cloud/"
			label={__('Cloud Dashboard', 'code-snippets')}
		/>

		{window.CODE_SNIPPETS?.urls.welcome && (
			<UpperNavItem
				name="welcome"
				url={window.CODE_SNIPPETS.urls.welcome}
				label={__("What's New", 'code-snippets')}
			/>)}

		{window.CODE_SNIPPETS?.urls.import && (
			<UpperNavItem
				name="import-snippets"
				url={window.CODE_SNIPPETS.urls.import}
				label={_x('Import', 'snippets', 'code-snippets')}
			/>)}

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

const UpperNav: React.FC = () => {
	const [isUpsellDialogOpen, setIsUpsellDialogOpen] = useState(false)

	return (
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

			<UpsellDialog isOpen={isUpsellDialogOpen} setIsOpen={setIsUpsellDialogOpen} />
		</div>
	)
}

interface SubpageItemProps {
	subpage: typeof SUBPAGES[number]
	label: string
	Icon: React.FC<SVGProps<SVGSVGElement>>
	isPro?: boolean
	isNew?: boolean
}

const SubpageItem: React.FC<SubpageItemProps> = ({ subpage, label, Icon, isPro, isNew }) =>
	<li>
		<a
			href={buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: subpage })}
			className={classnames(`${subpage}-link`, { 'active-link': subpage === searchParams.get('subpage') })}
		>
			<Icon aria-hidden="true" />
			<span className="toolbar-nav-label">{label}</span>
			{isNew && <span className="new-chip">{__('New', 'code-snippets')}</span>}
			{isPro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
		</a>
	</li>

const LowerNav = () =>
	<div className="code-snippets-toolbar-lower">
		<nav aria-label={__('Main features', 'code-snippets')}>
			<ul>
				<SubpageItem
					subpage="snippets"
					label={__('Snippets', 'code-snippets')}
					Icon={SnippetsIcon}
				/>

				{isLicensed() && (
					<SubpageItem
						subpage="blueprints"
						label={__('Blueprints', 'code-snippets')}
						Icon={BlueprintIcon}
					/>)}

				<SubpageItem
					subpage="cloud-community"
					label={__('Cloud Community', 'code-snippets')}
					Icon={CommunityIcon}
				/>

				<SubpageItem
					subpage="cloud-library"
					label={__('Cloud Library', 'code-snippets')}
					Icon={LibraryIcon}
					isPro
				/>

				{!isLicensed() && (
					<SubpageItem
						subpage="blueprints"
						label={__('Blueprints', 'code-snippets')}
						Icon={BlueprintIcon}
						isNew
					/>)}

				<SubpageItem
					subpage="ai-agent"
					label={__('AI Agent', 'code-snippets')}
					Icon={AiAgentIcon}
					isNew
				/>

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

export const Toolbar = () =>
	<div className="code-snippets-toolbar">
		<UpperNav />
		<LowerNav />
	</div>
