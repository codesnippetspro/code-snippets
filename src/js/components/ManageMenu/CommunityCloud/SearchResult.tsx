import React, { useState } from 'react'
import { __, _x, sprintf } from '@wordpress/i18n'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudSnippetPreviewModal } from '../../common/cloud/CloudSnippetPreviewModal'
import { CloudStatusIndicator } from '../../common/cloud/CloudStatusBadge'
import { CloudUpdateIcon } from '../../common/icons/CloudIcons'
import { SnippetCard } from '../../common/SnippetCard'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

interface CloudSnippetDetailsProps {
	snippet: CloudSnippetSchema
	setIsPreviewOpen: (isOpen: boolean) => void
}

const CloudSnippetDetails: React.FC<CloudSnippetDetailsProps> = ({ snippet, setIsPreviewOpen }) =>
	<div className="cloud-snippet card-inner">
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

		<div className="cloud-snippet-meta">
			<Badge name={getSnippetType(snippet)} />
			<span className="cloud-snippet-votes">
				<span className="dashicons dashicons-thumbs-up" aria-hidden="true"></span>
				<span>
					{snippet.vote_count}
					<span className="screen-reader-text">{` ${__('votes', 'code-snippets')}`}</span>
				</span>
			</span>
			{0 < snippet.tags.length
				? <span className="cloud-snippet-category">
					<strong>{__('Category: ', 'code-snippets')}</strong>
					{snippet.tags[0]}
				</span>
				: null}
		</div>

		{snippet.description && (
			<p className="cloud-snippet-description">
				{truncateChars(snippet.description)}
			</p>
		)}

		<p className="cloud-snippet-author">
			{_x('by ', 'snippet author', 'code-snippets')}
			<a href={`${window.CODE_SNIPPETS?.urls.cloud}/codevault/${snippet.codevault}`} target="_blank" rel="noopener noreferrer">
				{snippet.codevault}
			</a>
		</p>
	</div>

export interface SearchResultProps {
	snippet: CloudSnippetSchema
	isSelected?: boolean
	onSelectedChange?: (isSelected: boolean) => void
}

export const SearchResult: React.FC<SearchResultProps> = ({ snippet, isSelected = false, onSelectedChange }) => {
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
			footer={<>
				{snippet.update_available
					? <span className="cloud-snippet-update" title={__('Update available', 'code-snippets')}>
						<CloudUpdateIcon aria-label={__('Update available', 'code-snippets')} />
					</span>
					: null}
				<CloudStatusIndicator status={snippet.status} />
				<CloudSnippetDownloadButton snippet={snippet} />

				<Button secondary onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>
			</>}
		>
			<CloudSnippetDetails snippet={snippet} setIsPreviewOpen={setIsPreviewOpen} />

			<CloudSnippetPreviewModal snippet={snippet} isOpen={isPreviewOpen} setIsOpen={setIsPreviewOpen} />
		</SnippetCard>
	)
}
