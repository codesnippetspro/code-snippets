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
			<header>
				<h2>{DATA?.hero.name}</h2>
				<a
					className="button button-primary button-large"
					href={DATA?.hero.follow_url}
					target="_blank"
					rel="noopener noreferrer"
				>
					{__('Read more', 'code-snippets')}
				</a>
			</header>
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
		<h1>{__('Exclusive deals from our partners', 'code-snippets')}</h1>
		<section className="code-snippets-partners">
			{partners.map(({ title, follow_url, image_url }) =>
				<article key={title} className="code-snippets-card">
					<figure>
						<img src={image_url} alt={__('Partner image', 'code-snippets')} />
					</figure>
					<header>
						<h3>{title}</h3>
						<a href={follow_url} target="_blank" rel="noopener noreferrer">
							{__('Visit', 'code-snippets')}
						</a>
					</header>
				</article>)}
		</section>
	</>

interface ArticlesProps {
	articles: ImageLinkSchema[]
}

const Articles: React.FC<ArticlesProps> = ({ articles }) =>
	<>
		<h1>{__('Helpful articles', 'code-snippets')}</h1>
		<section className="code-snippets-articles">
			{articles.map(({ title, follow_url, image_url, description, category }) =>
				<article key={title} className="code-snippets-card">
					<figure>
						<img src={image_url} alt={__('Feature image', 'code-snippets')} />
					</figure>
					<header>
						<p className="item-category">{category}</p>
						<h2>{title}</h2>
						<p className="item-description">{description}</p>
						<a href={follow_url} className="button button-secondary" target="_blank" rel="noopener noreferrer">
							{__('Read more', 'code-snippets')}
						</a>
					</header>
				</article>)}
		</section>
	</>

export const WelcomeMenu = () =>
	<>
		<Toolbar />
		<div className="code-snippets-welcome">
			<h1>{__('Resources and Updates', 'code-snippets')}</h1>

			<div className="code-snippets-updates">
				<HeroImage />
				<Changelog />
			</div>

			{DATA?.features && <Articles articles={DATA.features} />}
			{DATA?.partners && <Partners partners={DATA.partners} />}
		</div>
	</>
