import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { Toolbar } from '../common/Toolbar'
import { Changelog } from './Changelog'
import type { ImageLinkSchema } from '../../types/schema/WelcomeSchema'

const DATA = window.CODE_SNIPPETS_WELCOME

const HeroImage = () => {
	const [isImageLoaded, setImageLoaded] = useState(false)

	return (
		<div className="code-snippets-hero">
			<div className="code-snippets-header-wrapper">
				<h3>{DATA?.hero.name}</h3>
				<a
					className="button button-primary button-large"
					href={DATA?.hero.follow_url}
					target="_blank"
					rel="noopener noreferrer"
				>
					{__('Read more', 'code-snippets')}
				</a>
			</div>
			<figure>
				{!isImageLoaded && <div className="code-snippets-loading-spinner"></div>}
				<img
					src={DATA?.hero.image_url}
					alt={__('Latest news image', 'code-snippets')}
					onLoad={() => setImageLoaded(true)}
				/>
			</figure>
		</div>
	)
}

interface PartnersProps {
	partners: ImageLinkSchema[]
}

const Partners: React.FC<PartnersProps> = ({ partners }) =>
	<>
		<h2>{__('Exclusive deals from our partners', 'code-snippets')}</h2>
		<ul className="code-snippets-partners">
			{partners.map(({ title, follow_url, image_url }) =>
				<li key={title} className="code-snippets-card">
					<figure>
						<img src={image_url} alt={__('Partner image', 'code-snippets')} />
					</figure>
					<div className="code-snippets-header-wrapper">
						<h3>{title}</h3>
						<a href={follow_url} target="_blank" rel="noopener noreferrer">
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
		<h2>{__('Helpful articles', 'code-snippets')}</h2>
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
						</a>
					</div>
				</li>)}
		</ul>
	</>

export const WelcomeMenu = () =>
	<>
		<Toolbar />
		<div className="code-snippets-welcome">
			<h2>{__('Resources and Updates', 'code-snippets')}</h2>

			<div className="code-snippets-updates">
				<HeroImage />
				<Changelog />
			</div>

			{DATA?.features && <Articles articles={DATA.features} />}
			{DATA?.partners && <Partners partners={DATA.partners} />}
		</div>
	</>
