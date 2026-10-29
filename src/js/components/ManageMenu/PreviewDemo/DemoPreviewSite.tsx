import React from 'react'
import classnames from 'classnames'
import { __, sprintf } from '@wordpress/i18n'
import { Badge } from '../../common/Badge'
import { hasReached } from './types'
import type { DemoStage } from './types'

/** Fallback used when the site has no name set, so the mock header is never blank. */
const FALLBACK_SITE_NAME = __('Your site', 'code-snippets')

const getSiteName = (): string => {
	const siteName = window.CODE_SNIPPETS_MANAGE?.previewDemo?.siteName.trim()
	return undefined === siteName || '' === siteName ? FALLBACK_SITE_NAME : siteName
}

const getSiteHost = (): string => {
	const homeUrl = window.CODE_SNIPPETS_MANAGE?.previewDemo?.homeUrl

	try {
		return homeUrl ? new URL(homeUrl).host : 'example.com'
	} catch {
		return 'example.com'
	}
}

interface DemoCodeBoxProps {
	typedColor: string
	isTyping: boolean
}

/**
 * A floating stand-in for the snippet editor, showing the one rule the
 * walkthrough edits. The script drives the text, so nothing here takes input.
 */
const DemoCodeBox: React.FC<DemoCodeBoxProps> = ({ typedColor, isTyping }) =>
	<div className="preview-demo-code" aria-label={__('Snippet code', 'code-snippets')} role="group">
		<div className="preview-demo-code__header">
			<Badge name="css" small />
			<span className="preview-demo-code__title">{__('Page background', 'code-snippets')}</span>
			<span className="preview-demo-code__state">{__('Draft', 'code-snippets')}</span>
		</div>

		<pre className="preview-demo-code__body">
			<span className="preview-demo-code__selector">body</span>{' {\n\t'}
			<span className="preview-demo-code__property">background-color</span>{': '}
			<span className="preview-demo-code__value">
				<span className="preview-demo-code__swatch" style={{ backgroundColor: typedColor }} aria-hidden="true" />
				{typedColor}
				{isTyping && <span className="preview-demo-code__caret" aria-hidden="true" />}
			</span>
			{';\n}'}
		</pre>
	</div>

/** The log the real preview pins to the corner of the page it is testing. */
const DemoPreviewLog: React.FC = () =>
	<div className="preview-demo-log">
		<div className="preview-demo-log__head">
			<span className="preview-demo-log__title">{__('Snippet Preview Log', 'code-snippets')}</span>
			<span className="preview-demo-log__badges">
				<span className="preview-demo-log__badge is-error">{__('0 errors', 'code-snippets')}</span>
				<span className="preview-demo-log__badge is-warning">{__('0 warnings', 'code-snippets')}</span>
			</span>
		</div>
		<p className="preview-demo-log__body">
			{__('No snippet-related warnings or errors captured yet.', 'code-snippets')}
		</p>
	</div>

interface DemoPreviewSiteProps {
	stage: DemoStage
	typedColor: string
	pageColor: string
}

/**
 * A mock browser tab showing the site with the draft applied. Only the colour
 * changes; the page is drawn here and nothing on the real site is touched.
 */
export const DemoPreviewSite: React.FC<DemoPreviewSiteProps> = ({ stage, typedColor, pageColor }) => {
	const siteName = getSiteName()

	return (
		<div className="preview-demo-browser">
			<div className="preview-demo-browser__bar" aria-hidden="true">
				<span className="preview-demo-browser__dots"><i /><i /><i /></span>
				<span className="preview-demo-browser__address">
					{`${getSiteHost()}/?code-snippets-preview=draft`}
				</span>
			</div>

			<div className="preview-demo-site" style={{ backgroundColor: pageColor }}>
				<p className="preview-demo-site__notice">
					<span className="dashicons dashicons-visibility" aria-hidden="true" />
					{__('Previewing a draft snippet. Only you can see this; visitors see your live site.', 'code-snippets')}
				</p>

				<header className="preview-demo-site__header">
					<span className="preview-demo-site__name">{siteName}</span>
					<span className="preview-demo-site__nav" aria-hidden="true">
						<span>{__('Home', 'code-snippets')}</span>
						<span>{__('Blog', 'code-snippets')}</span>
						<span>{__('Contact', 'code-snippets')}</span>
					</span>
				</header>

				<div className="preview-demo-site__hero">
					<h2>
						{sprintf(
							/* translators: %s: name of the current site. */
							__('Welcome to %s', 'code-snippets'),
							siteName
						)}
					</h2>
					<p>{__('This is how the front page looks with the draft snippet applied.', 'code-snippets')}</p>
				</div>

				<div className="preview-demo-site__cards" aria-hidden="true">
					<span /><span /><span />
				</div>

				{hasReached(stage, 'opened') &&
					<DemoCodeBox typedColor={typedColor} isTyping={'typing' === stage} />}

				{hasReached(stage, 'log') && <DemoPreviewLog />}

				<div className={classnames('preview-demo-site__updating', { 'is-visible': 'updating' === stage })} aria-hidden="true">
					<span className="preview-demo-site__spinner" />
					<span>{__('Updating preview…', 'code-snippets')}</span>
				</div>
			</div>
		</div>
	)
}
