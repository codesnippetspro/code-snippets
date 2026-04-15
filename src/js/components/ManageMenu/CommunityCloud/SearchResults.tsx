import React, { useEffect, useState } from 'react'
import classnames from 'classnames'
import { __, _x } from '@wordpress/i18n'
import { Modal, Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { Prism } from '../../../utils/Prism'
import { REST_BASES } from '../../../utils/restAPI'
import { isLicensed } from '../../../utils/screen'
import { getSnippetEditUrl, getSnippetType, isProSnippet } from '../../../utils/snippets/snippets'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { ErrorTooltip } from '../../common/Tooltip'
import { STATUS_LABELS } from './SearchFilters'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

const MAX_DESCRIPTION_LENGTH = 150

interface DownloadSnippetResponse {
	success: boolean
	snippet_id: number
	link_id: number
}

interface CloudSnippetDetailsProps {
	snippet: CloudSnippetSchema
	setIsPreviewOpen: (isOpen: boolean) => void
}

const CloudSnippetDetails: React.FC<CloudSnippetDetailsProps> = ({ snippet, setIsPreviewOpen }) =>
	<div className="cloud-snippet">
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
				<span>{snippet.vote_count}</span>
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
				{snippet.description.length > MAX_DESCRIPTION_LENGTH
					? `${snippet.description.slice(0, MAX_DESCRIPTION_LENGTH)}…`
					: snippet.description}
			</p>
		)}

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

interface DownloadButtonProps {
	snippet: CloudSnippetSchema
}

const DownloadButton: React.FC<DownloadButtonProps> = ({ snippet }) => {
	const { api } = useRestAPI()
	const [isWorking, setIsWorking] = useState(false)
	const [errorMessage, setErrorMessage] = useState<string>()
	const [localSnippetId, setLocalSnippetId] = useState<number>()

	const handleDownload = () => {
		setIsWorking(true)
		setErrorMessage(undefined)

		api.post<DownloadSnippetResponse>(`${REST_BASES.cloud}/${snippet.id}/download`)
			.then(response => {
				setLocalSnippetId(response.snippet_id)
			})
			.catch((error: unknown) => {
				setErrorMessage('string' === typeof error
					? error
					: __('An error occurred while trying to download the snippet. Please try again later.', 'code-snippets'))
			})
			.finally(() => setIsWorking(false))
	}

	const DownloadOrViewButton = () => {
		if (localSnippetId) {
			return (
				<a className="button button-primary" href={getSnippetEditUrl({ id: localSnippetId })} target="_blank" rel="noreferrer">
					{__('View', 'code-snippets')}
				</a>
			)
		}

		if (isProSnippet(snippet) && !isLicensed()) {
			return (
				<Button primary disabled>{__('Pro only', 'code-snippets')}</Button>
			)
		}

		return (
			<Button primary onClick={handleDownload} disabled={isWorking}>
				{__('Download', 'code-snippets')}
			</Button>
		)
	}

	return (
		<>
			{isWorking && <Spinner />}
			{errorMessage && <ErrorTooltip message={errorMessage} />}
			<DownloadOrViewButton />
		</>
	)
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
		<li className="cloud-search-result">
			<CloudSnippetDetails snippet={snippet} setIsPreviewOpen={setIsPreviewOpen} />

			<footer>
				<span className={classnames(
					'cloud-snippet-status',
					`cloud-snippet-status-${CloudStatus[snippet.status].toLowerCase().replace('_', '-')}`
				)}>
					{STATUS_LABELS[snippet.status]}
				</span>

				<DownloadButton snippet={snippet} />

				<Button secondary onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>

			</footer>

			<PreviewModal snippet={snippet} isOpen={isPreviewOpen} setIsOpen={setIsPreviewOpen} />
		</li>
	)
}

interface SearchResultsProps {
	results: CloudSnippetSchema[]
}

export const SearchResults: React.FC<SearchResultsProps> = ({ results }) =>
	<ul className="cloud-search-results">
		{results.map(result =>
			<SearchResult key={result.id} snippet={result} />)}
	</ul>
