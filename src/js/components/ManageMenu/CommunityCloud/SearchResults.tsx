import React, { useEffect, useState } from 'react'
import classnames from 'classnames'
import { __, _x } from '@wordpress/i18n'
import { Modal } from '@wordpress/components'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { Prism } from '../../../utils/Prism'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { STATUS_LABELS } from './SearchFilters'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

const MAX_DESCRIPTION_LENGTH = 150

interface CloudSnippetDetailsProps {
	snippet: CloudSnippetSchema
	setIsPreviewOpen: (isOpen: boolean) => void
}

const CloudSnippetDetails: React.FC<CloudSnippetDetailsProps> = ({ snippet, setIsPreviewOpen }) =>
	<div className="cloud-snippet">
		<h3>
			<a
				href="#"
				title={__('Preview this snippet', 'code-snippets')}
				onClick={() => setIsPreviewOpen(true)}
			>
				{snippet.name}
			</a>
		</h3>

		<div className="cloud-snippet-meta">
			<Badge name={getSnippetType(snippet)} />
			<span className="cloud-snippet-votes">
				<span className="dashicons dashicons-thumbs-up"></span>
				<span>{snippet.vote_count}</span>
			</span>
			{0 < snippet.tags.length
				? <span className="cloud-snippet-category">
					<strong>{__('Category: ', 'code-snippets')}</strong>
					{snippet.tags[0]}
				</span>
				: null}
		</div>

		<p className="cloud-snippet-description">
			{snippet.description.length > MAX_DESCRIPTION_LENGTH
				? `${snippet.description.slice(0, MAX_DESCRIPTION_LENGTH)}…`
				: snippet.description}
		</p>

		<p className="cloud-snippet-author">
			{_x('by ', 'snippet author', 'code-snippets')}
			<a href={`https://codesnippets.cloud/codevault/${snippet.codevault}`} target="_blank" rel="noopener noreferrer">
				{snippet.codevault}
			</a>
		</p>
	</div>

interface PreviewModalProps {
	isOpen: boolean
	snippet: CloudSnippetSchema
	setIsOpen: (isOpen: boolean) => void
}

const PreviewModal: React.FC<PreviewModalProps> = ({ snippet, isOpen, setIsOpen }) => {
	const snippetType = getSnippetType(snippet)

	return isOpen
		? <Modal onRequestClose={() => setIsOpen(false)} title={snippet.name}>
			<pre className="line-numbers">
				<code className={`language-${snippetType}`}>
					{'php' === snippetType ? '<?php\n\n' : ''}
					{snippet.code}
				</code>
			</pre>
		</Modal>
		: null
}

interface SearchResultProps {
	snippet: CloudSnippetSchema
}

const SearchResult: React.FC<SearchResultProps> = ({ snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	useEffect(() => {
		if (isPreviewOpen) {
			Prism.highlightAll()
		}
	}, [isPreviewOpen])

	return (
		<div className="cloud-search-result">
			<CloudSnippetDetails snippet={snippet} setIsPreviewOpen={setIsPreviewOpen} />

			<footer>
				<span className={classnames(
					'cloud-snippet-status',
					`cloud-snippet-status-${CloudStatus[snippet.status].toLowerCase().replace('_', '-')}`
				)}>
					{STATUS_LABELS[snippet.status]}
				</span>

				<Button secondary onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>
			</footer>

			<PreviewModal snippet={snippet} isOpen={isPreviewOpen} setIsOpen={setIsPreviewOpen} />
		</div>
	)
}

interface SearchResultsProps {
	results: CloudSnippetSchema[]
}

export const SearchResults: React.FC<SearchResultsProps> = ({ results }) =>
	<div className="cloud-search-results">
		{results.map(result =>
			<SearchResult key={result.id} snippet={result} />)}
	</div>
