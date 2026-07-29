import { humanTimeDiff } from '@wordpress/date'
import { __, _x, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { stripTags, truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudSnippetPreviewModal } from '../../common/cloud/CloudSnippetPreviewModal'
import { CloudStatusIndicator } from '../../common/cloud/CloudStatusBadge'
import { CloudUpdateIcon } from '../../common/icons/CloudIcons'
import { SnippetCard } from '../../common/SnippetCard'
import type { ReactNode } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

export interface CloudSnippetAuthorProps {
	snippet: CloudSnippetSchema
}

export const CloudSnippetAuthor: React.FC<CloudSnippetAuthorProps> = ({ snippet }) =>
	<p className="cloud-snippet-author">
		<a
			href={`${window.CODE_SNIPPETS?.urls.cloud}/codevault/${snippet.codevault}`}
			target="_blank"
			rel="noopener noreferrer"
		>
			{sprintf(
				_x('By %s', 'snippet author', 'code-snippets'),
				snippet.codevault
			)}
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
					? <span className="cloud-snippet-tags">{snippet.tags.join(', ')}</span>
					: null}

				{snippet.updated
					? <time className="snippet-card-modified" dateTime={snippet.updated} title={snippet.updated}>
						{sprintf(
							/* translators: %s: human-readable time difference, including "ago" suffix. */
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

				<CloudSnippetDownloadButton snippet={snippet} />
			</>}
		>
			<CloudSnippetDetails snippet={snippet} author={author} setIsPreviewOpen={setIsPreviewOpen} />

			<CloudSnippetPreviewModal
				snippet={snippet}
				isOpen={isPreviewOpen}
				setIsOpen={setIsPreviewOpen}
			/>
		</SnippetCard>
	)
}
