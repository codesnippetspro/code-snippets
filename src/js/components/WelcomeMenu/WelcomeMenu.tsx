import { __, sprintf } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { revealLocationHash } from '../../utils/urls'
import { ScreenMetaSlot } from '../common/ScreenMetaSlot'
import { SubnavTabs, getTabFromQuery } from '../common/SubnavTabs'
import { Toolbar } from '../common/Toolbar'
import { Changelog } from './Changelog'
import type { ImageLinkSchema } from '../../types/schema/WelcomeSchema'
import type { SubnavTab } from '../common/SubnavTabs'

const DATA = window.CODE_SNIPPETS_WELCOME
const TAB_QUERY_PARAM = 'tab'

type WelcomeTabName = 'whats-new' | 'changelog' | 'resources'

const TABS: SubnavTab<WelcomeTabName>[] = [
	{
		name: 'whats-new',
		label: __("What's new", 'code-snippets')
	},
	{
		name: 'changelog',
		label: __('Changelog', 'code-snippets')
	},
	{
		name: 'resources',
		label: __('Resources', 'code-snippets')
	}
]

const WhatsNew = () => {
	const [loadedImages, setLoadedImages] = useState<string[]>([])

	return (
		<>
			{DATA?.hero.map(({ name, follow_url, image_url }) => (
				<div key={follow_url} className="code-snippets-card code-snippets-news-item">
					<div className="code-snippets-header-wrapper">
						<h2>{name}</h2>
						<a
							className="button button-primary button-large"
							href={follow_url}
							target="_blank"
							rel="noopener noreferrer"
						>
							{__('Read more', 'code-snippets')}
							<span className="screen-reader-text">
								{__('(opens in a new tab)', 'code-snippets')}
							</span>
						</a>
					</div>
					<figure>
						{!loadedImages.includes(image_url) && <div className="code-snippets-loading-spinner" aria-hidden="true"></div>}
						<img
							src={image_url}
							alt={__('Latest news image', 'code-snippets')}
							onLoad={() => setLoadedImages(images => [...images, image_url])}
						/>
					</figure>
				</div>
			))}
		</>
	)
}

interface PartnersProps {
	partners: ImageLinkSchema[]
}

const Partners: React.FC<PartnersProps> = ({ partners }) =>
	<>
		<h2 id="partner-deals">{__('Exclusive deals from our partners', 'code-snippets')}</h2>
		<ul className="code-snippets-partners">
			{partners.map(({ title, follow_url, image_url }) =>
				<li key={title} className="code-snippets-card">
					<figure>
						<img src={image_url} alt={__('Partner image', 'code-snippets')} />
					</figure>
					<div className="code-snippets-header-wrapper">
						<h3>{title}</h3>
						<a
							href={follow_url}
							target="_blank"
							rel="noopener noreferrer"
							aria-label={sprintf(
								/* translators: %s: partner name. */
								__('Visit %s (opens in a new tab)', 'code-snippets'),
								title
							)}
						>
							{__('Visit', 'code-snippets')}
						</a>
					</div>
				</li>)}
		</ul>
	</>

interface ArticlesProps {
	articles: ImageLinkSchema[]
}

const Articles: React.FC<ArticlesProps> = ({ articles }) =>
	<>
		<h2 id="helpful-articles">{__('Helpful articles', 'code-snippets')}</h2>
		<ul className="code-snippets-articles">
			{articles.map(({ title, follow_url, image_url, description, category }) =>
				<li key={title} className="code-snippets-card">
					<figure>
						<img src={image_url} alt={__('Feature image', 'code-snippets')} />
					</figure>
					<div className="code-snippets-header-wrapper">
						<p className="item-category">{category}</p>
						<h3>{title}</h3>
						<p className="item-description">{description}</p>
						<a href={follow_url} className="button button-secondary" target="_blank" rel="noopener noreferrer">
							{__('Read more', 'code-snippets')}
							<span className="screen-reader-text">
								{__('(opens in a new tab)', 'code-snippets')}
							</span>
						</a>
					</div>
				</li>)}
		</ul>
	</>

export const WelcomeMenu = () => {
	const [currentTab, setCurrentTab] = useState(() => getTabFromQuery(TABS, TAB_QUERY_PARAM))

	useEffect(revealLocationHash, [])

	return (
		<>
			<Toolbar />

			<SubnavTabs
				ariaLabel={__('Welcome sections', 'code-snippets')}
				tabs={TABS}
				currentTab={currentTab}
				setCurrentTab={setCurrentTab}
				queryParamName={TAB_QUERY_PARAM}
			/>

			<ScreenMetaSlot />

			<div className="snippets-page-header">
				<h1>{currentTab.label}</h1>
			</div>

			<hr className="wp-header-end" />

			<div className="code-snippets-welcome">
				{'whats-new' === currentTab.name && (
					<div className="code-snippets-welcome-panel code-snippets-updates whats-new-panel">
						<WhatsNew />
					</div>
				)}

				{'changelog' === currentTab.name && (
					<div className="code-snippets-welcome-panel changelog-panel">
						<Changelog />
					</div>
				)}

				{'resources' === currentTab.name && (
					<div className="code-snippets-welcome-panel resources-panel">
						{DATA?.features && <Articles articles={DATA.features} />}
						{DATA?.partners && <Partners partners={DATA.partners} />}
					</div>
				)}
			</div>
		</>
	)
}
