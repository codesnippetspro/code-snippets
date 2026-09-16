import { humanTimeDiff } from '@wordpress/date'
import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { stripTags, truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudStatusIndicator } from '../../common/cloud/CloudStatusBadge'
import { CloudUpdateIcon } from '../../common/icons/CloudUpdateIcon'
import { SnippetCard } from '../../common/snippets/SnippetCard'
import { CloudSnippetPreviewModal } from '../../common/snippets/SnippetPreviewModal'
import { useCloudSearch } from './WithCloudSearchContext'
import type { ReactNode } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

export interface CloudSnippetAuthorProps {
	codevaultSlug: string
}

export const CloudSnippetAuthor: React.FC<CloudSnippetAuthorProps> = ({ codevaultSlug }) =>
	<p className="cloud-snippet-author">
		<a
			href={`${window.CODE_SNIPPETS?.urls.cloud}/codevault/${codevaultSlug}`}
			target="_blank"
			rel="noopener noreferrer"
		>
			{// translators: %s: cloud library author name.
				sprintf(__('By %s', 'code-snippets'), codevaultSlug)}
		</a>
	</p>

interface CloudSnippetDetailsProps {
	snippet: CloudSnippetSchema
	author?: ReactNode
	setIsPreviewOpen: (isOpen: boolean) => void
}

const CloudSnippetDetails: React.FC<CloudSnippetDetailsProps> = ({
	snippet,
	author,
	setIsPreviewOpen
}) =>
	<div className="cloud-snippet card-inner">
		<div className="snippet-card-header">
			<Badge name={getSnippetType(snippet)} />

			<h3>
				<button
					type="button"
					className="cloud-snippet-title-button"
					title={__('Preview this snippet', 'code-snippets')}
					onClick={() => {
						setIsPreviewOpen(true)
					}}
				>
					{snippet.name}
				</button>
			</h3>
		</div>

		{0 < snippet.tags.length || snippet.updated
			? <div className="snippet-card-meta">
				{0 < snippet.tags.length
					? <span className="cloud-snippet-tags">
						<span className="snippet-card-tags-label">
							{__('Tags:', 'code-snippets')}
						</span> {snippet.tags.join(', ')}
					</span>
					: null}

				{snippet.updated
					? <time className="snippet-card-modified" dateTime={snippet.updated} title={snippet.updated}>
						{sprintf(
							/* translators: %s: human-readable time difference. */
							__('Modified %s', 'code-snippets'),
							humanTimeDiff(snippet.updated, undefined)
						)}
					</time>
					: null}
			</div>
			: null}

		{snippet.description
			? <p className="snippet-description-content">
				{truncateChars(stripTags(snippet.description))}
			</p>
			: null}

		{author}
	</div>

export interface SearchResultProps {
	snippet: CloudSnippetSchema
	author?: ReactNode
	isSelected?: boolean
	onSelectedChange?: (isSelected: boolean) => void
}

export const SearchResult: React.FC<SearchResultProps> = ({
	snippet,
	author,
	isSelected = false,
	onSelectedChange
}) => {
	const { doSearch } = useCloudSearch()
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<SnippetCard
			className="cloud-search-result"
			isSelected={isSelected}
			onSelectedChange={onSelectedChange}
			selectionLabel={sprintf(
				/* translators: %s: name of the snippet. */
				__('Select %s', 'code-snippets'),
				snippet.name
			)}
			footerStatus={<>
				<CloudStatusIndicator status={snippet.status} />

				{snippet.update_available
					? <span className="cloud-snippet-update" title={__('Update available', 'code-snippets')}>
						<CloudUpdateIcon aria-label={__('Update available', 'code-snippets')} />
					</span>
					: null}
			</>}
			footer={<>
				<Button secondary onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>

				<CloudSnippetDownloadButton snippet={snippet} onDownloaded={doSearch} />
			</>}
		>
			<CloudSnippetDetails snippet={snippet} author={author} setIsPreviewOpen={setIsPreviewOpen} />

			{isPreviewOpen && (
				<CloudSnippetPreviewModal snippet={snippet} setIsOpen={setIsPreviewOpen} onDownloaded={doSearch} />)}
		</SnippetCard>
	)
}
