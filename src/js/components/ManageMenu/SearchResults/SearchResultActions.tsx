import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { handleUnknownError } from '../../../utils/errors'
import { isLicensed } from '../../../utils/screen'
import { getSnippetEditUrl, isProSnippet } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { useCloudSnippetDownload } from '../common/cloud/CloudSnippetDownloadButton'
import { CloudSnippetPreviewModal, SnippetPreviewModal } from '../common/snippets/SnippetPreviewModal'
import { SnippetActionButton } from '../SnippetsTable/RowActions'
import type { ReactNode } from 'react'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { SearchItem, SearchSnippetSummary } from '../../../types/Search'
import type { Snippet } from '../../../types/Snippet'

/**
 * Separate a result's actions with a bar, as the row actions of the snippets table are.
 */
const joinActions = (actions: ReactNode[]): ReactNode =>
	actions
		.filter(action => action)
		.reduce<ReactNode>((joined, action) => null === joined ? action : <>{joined} | {action}</>, null)

interface SnippetPreviewActionProps {
	summary: SearchSnippetSummary
}

/**
 * Search results never carry a snippet's full code, so it is fetched when the preview is asked for.
 */
const SnippetPreviewAction: React.FC<SnippetPreviewActionProps> = ({ summary }) => {
	const api = useSnippetsAPI()
	const [snippet, setSnippet] = useState<Snippet>()
	const [isOpen, setIsOpen] = useState(false)

	return (
		<>
			<SnippetActionButton
				label={__('Preview', 'code-snippets')}
				action={() => api.fetch(summary.id, summary.network).then(fetched => {
					setSnippet(fetched)
					setIsOpen(true)
				})}
			/>

			{isOpen && snippet && <SnippetPreviewModal snippet={snippet} setIsOpen={setIsOpen} />}
		</>
	)
}

interface SnippetActionsProps {
	item: SearchItem
	summary: SearchSnippetSummary
}

// As in the snippets table, a shared network snippet can only be opened by those who manage the network.
const SnippetActions: React.FC<SnippetActionsProps> = ({ item, summary }) =>
	summary.shared_network && !window.CODE_SNIPPETS_MANAGE?.hasNetworkCap ? null : joinActions([
		<a key="edit" href={item.url}>
			{summary.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
		</a>,
		<SnippetPreviewAction key="preview" summary={summary} />
	])

export interface SnippetActivationSwitchProps {
	item: SearchItem
	summary: SearchSnippetSummary
	isActive: boolean
	setIsActive: (isActive: boolean) => void
}

/**
 * The same switch the snippets table uses to activate and deactivate a snippet.
 */
export const SnippetActivationSwitch: React.FC<SnippetActivationSwitchProps> = ({ item, summary, isActive, setIsActive }) => {
	const api = useSnippetsAPI()
	const [isWorking, setIsWorking] = useState(false)
	const label = isActive
		// translators: %s: snippet name.
		? sprintf(__('Deactivate %s', 'code-snippets'), item.title)
		// translators: %s: snippet name.
		: sprintf(__('Activate %s', 'code-snippets'), item.title)

	return (
		<input
			type="checkbox"
			role="switch"
			className="switch"
			checked={isActive}
			aria-checked={isActive}
			disabled={isWorking}
			title={label}
			aria-label={label}
			onChange={() => {
				setIsWorking(true)

				;(isActive ? api.deactivate(summary) : api.activate(summary))
					.then(() => setIsActive(!isActive))
					.catch(handleUnknownError)
					.finally(() => setIsWorking(false))
			}}
		/>
	)
}

interface CloudActionsProps {
	item: SearchItem
	snippet: CloudSnippetSchema
}

const CloudActions: React.FC<CloudActionsProps> = ({ item, snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)
	const { isDownloading, localSnippetId, errorMessage, download } = useCloudSnippetDownload({ snippet })
	const canDownload = !isProSnippet(snippet) || isLicensed()

	return (
		<>
			{joinActions([
				<Button key="preview" link onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>,

				localSnippetId
					? <a key="edit" href={getSnippetEditUrl({ id: localSnippetId })}>{__('Edit', 'code-snippets')}</a>
					: canDownload && (
						<Button key="download" link disabled={isDownloading} onClick={download}>
							{isDownloading ? __('Downloading…', 'code-snippets') : __('Download', 'code-snippets')}
						</Button>),

				'cloud' === item.source && (
					<a key="cloud" href={window.CODE_SNIPPETS?.urls.cloud} target="_blank" rel="noopener noreferrer">
						{__('Open Cloud Dashboard', 'code-snippets')}
						<span className="screen-reader-text">{__('(opens in a new tab)', 'code-snippets')}</span>
					</a>)
			])}

			{errorMessage && <span className="search-result-error" role="alert">{errorMessage}</span>}

			{isPreviewOpen && <CloudSnippetPreviewModal snippet={snippet} setIsOpen={setIsPreviewOpen} />}
		</>
	)
}

export interface SearchResultActionsProps {
	item: SearchItem
}

export const SearchResultActions: React.FC<SearchResultActionsProps> = ({ item }) => {
	if (item.snippet) {
		return <SnippetActions item={item} summary={item.snippet} />
	}

	if (item.cloud) {
		return <CloudActions item={item} snippet={item.cloud} />
	}

	switch (item.source) {
		case 'settings':
			return <a href={item.url}>{__('Open setting', 'code-snippets')}</a>

		case 'stats':
			return <a href={item.url}>{__('View stats', 'code-snippets')}</a>

		case 'docs':
			return (
				<a href={item.url} target="_blank" rel="noopener noreferrer">
					{__('Read article', 'code-snippets')}
					<span className="dashicons dashicons-external" aria-hidden="true"></span>
					<span className="screen-reader-text">{__('(opens in a new tab)', 'code-snippets')}</span>
				</a>
			)

		default:
			return item.url ? <a href={item.url}>{__('Open', 'code-snippets')}</a> : null
	}
}
