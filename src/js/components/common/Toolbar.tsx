import { __, _x } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { isLicensed, shouldShowUpsell } from '../../utils/screen'
import { buildUrl, fetchConstQueryParam } from '../../utils/urls'
import { SUBPAGES, SUBPAGE_ENTRIES, type SubpageName } from '../ManageMenu/subpages'
import { hasSeenDemo } from './demo/useDemoSeen'
import { AiAgentIcon } from './icons/AiAgentIcon'
import { BlueprintIcon } from './icons/BlueprintIcon'
import { CommunityIcon } from './icons/CommunityIcon'
import { LibraryIcon } from './icons/LibraryIcon'
import { SettingsIcon } from './icons/SettingsIcon'
import { SnippetsIcon } from './icons/SnippetsIcon'
import { UpsellDialog } from './UpsellDialog'
import type { SVGProps } from 'react'

const searchParams = new URLSearchParams(window.location.search)

const managePageSlug = window.CODE_SNIPPETS?.urls.manage
	? new URL(window.CODE_SNIPPETS.urls.manage).searchParams.get('page')
	: null

// The manage screen resolves an absent or unrecognised `subpage` to the first subpage, so
// mirror that fallback here — comparing against the raw param would leave every lower-nav
// tab inactive on the default Snippets view, which is where the page opens.
const activeSubpage = searchParams.get('page') === managePageSlug
	? fetchConstQueryParam('subpage', SUBPAGES) ?? SUBPAGES[0]
	: null

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
		{window.CODE_SNIPPETS?.urls.insights && (
			<UpperNavItem
				name="insights"
				url={window.CODE_SNIPPETS.urls.insights}
				label={__('Insights', 'code-snippets')}
			/>)}

		{window.CODE_SNIPPETS?.urls.import && (
			<UpperNavItem
				name="import-snippets"
				url={window.CODE_SNIPPETS.urls.import}
				label={_x('Import', 'snippets', 'code-snippets')}
			/>)}

		{window.CODE_SNIPPETS?.urls.welcome && (
			<UpperNavItem
				name="welcome"
				url={window.CODE_SNIPPETS.urls.welcome}
				label={__("What's New", 'code-snippets')}
			/>)}

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
	subpage: SubpageName
	label: string
	Icon: React.FC<SVGProps<SVGSVGElement>>
}

const SubpageItem: React.FC<SubpageItemProps> = ({ subpage, label, Icon }) => {
	const { demo, isPro } = SUBPAGE_ENTRIES[subpage]
	const isNew = 'announce' === demo && !hasSeenDemo(subpage)

	return (
		<li>
			<a
				href={buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: subpage })}
				className={classnames(`${subpage}-link`, { 'active-link': subpage === activeSubpage })}
			>
				<Icon aria-hidden="true" />
				<span className="toolbar-nav-label">{label}</span>
				{demo && (isNew
					? <span className="new-chip">{__('New', 'code-snippets')}</span>
					: <span className="demo-chip">{__('Demo', 'code-snippets')}</span>)}
				{isPro && !isLicensed() && <span className="pro-chip">{__('Pro', 'code-snippets')}</span>}
			</a>
		</li>
	)
}

const SettingsItem = () =>
	window.CODE_SNIPPETS?.urls.settings
		? <li>
			<a
				href={window.CODE_SNIPPETS.urls.settings}
				className={classnames('settings-link', {
					'active-link': 'snippets-settings' === searchParams.get('page')
				})}
			>
				<SettingsIcon aria-hidden="true" />
				<span className="toolbar-nav-label">{__('Settings', 'code-snippets')}</span>
			</a>
		</li>
		: null

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
				/>

				{!isLicensed() && (
					<SubpageItem
						subpage="blueprints"
						label={__('Blueprints', 'code-snippets')}
						Icon={BlueprintIcon}
					/>)}

				<SubpageItem
					subpage="ai-agent"
					label={__('AI Agent', 'code-snippets')}
					Icon={AiAgentIcon}
				/>

				<SettingsItem />
			</ul>
		</nav>
	</div>

export const Toolbar = () =>
	<div className="code-snippets-toolbar">
		<UpperNav />
		<LowerNav />
	</div>
