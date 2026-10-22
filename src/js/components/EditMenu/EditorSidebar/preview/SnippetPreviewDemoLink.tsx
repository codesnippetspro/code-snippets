import React, { useId } from 'react'
import { __ } from '@wordpress/i18n'
import { isLicensed } from '../../../../utils/screen'
import { isCondition } from '../../../../utils/snippets/snippets'
import { buildUrl } from '../../../../utils/urls'
import { Badge } from '../../../common/Badge'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

/**
 * Where the Pro preview controls sit in the sidebar. It runs nothing: the
 * button opens a scripted walkthrough of the feature in a new tab, so the
 * snippet being edited is left exactly as it is.
 */
export const SnippetPreviewDemoLink: React.FC = () => {
	const { snippet } = useSnippetForm()
	const headingId = useId()

	if (isCondition(snippet)) {
		return null
	}

	return (
		<section className="snippet-editor-sidebar-settings snippet-preview-demo" aria-labelledby={headingId}>
			<div className="snippet-preview-demo__header">
				<h2 id={headingId} className="snippet-preview-demo__heading">{__('Preview Snippet', 'code-snippets')}</h2>
				{!isLicensed() && <Badge name="pro" small>{__('Pro', 'code-snippets')}</Badge>}
			</div>

			<p className="snippet-preview-demo__description">
				{__('Test a draft on a real page or admin screen before you save or activate it.', 'code-snippets')}
			</p>

			<a
				className="button button-large snippet-preview-demo__button"
				href={buildUrl(window.CODE_SNIPPETS?.urls.manage, { subpage: 'preview' })}
				target="_blank"
				rel="noopener noreferrer"
			>
				<span className="dashicons dashicons-visibility" aria-hidden="true" />
				{__('Preview', 'code-snippets')}
				<span className="screen-reader-text">{__('(opens a demo in a new tab)', 'code-snippets')}</span>
			</a>
		</section>
	)
}
