import React, { useState } from 'react'
import classnames from 'classnames'
import { __, sprintf } from '@wordpress/i18n'
import { RawHTML } from '@wordpress/element'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadSnippetExportFile } from '../../../utils/files'
import { isNetworkAdmin } from '../../../utils/screen'
import { cloneSnippetObject, getSnippetDisplayName, getSnippetEditUrl, getSnippetType, isSnippetActive } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { DeleteButton } from '../../common/DeleteButton'
import { SnippetCard } from '../../common/SnippetCard'
import { SnippetPreviewModal } from '../../common/SnippetPreviewModal'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { ActivateColumn, DateColumn, PriorityColumn, SnippetExtraIcons, SnippetName, TagsColumn, TypeColumn } from './TableColumns'
import type { Snippet } from '../../../types/Snippet'

interface SnippetCardActionsProps {
	snippet: Snippet
}

const CardPreviewButton: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<>
			<Button secondary onClick={() => setIsPreviewOpen(true)}>
				{__('Preview', 'code-snippets')}
			</Button>

			<SnippetPreviewModal
				title={getSnippetDisplayName(snippet)}
				code={snippet.code}
				type={getSnippetType(snippet)}
				isOpen={isPreviewOpen}
				setIsOpen={setIsPreviewOpen}
			/>
		</>
	)
}

const EditCloneExportButtons: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	return (
		<>
			<a className="button button-primary" href={getSnippetEditUrl(snippet)}>
				{snippet.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
			</a>

			<Button
				secondary
				onClick={() => {
					api.create(cloneSnippetObject(snippet))
						.then(refreshSnippetsList)
						.catch(handleUnknownError)
				}}
			>
				{__('Clone', 'code-snippets')}
			</Button>

			<Button
				secondary
				onClick={() => {
					api.export(snippet)
						.then(response => downloadSnippetExportFile(response, snippet))
						.catch(handleUnknownError)
				}}
			>
				{__('Export', 'code-snippets')}
			</Button>
		</>
	)
}

const SnippetCardActions: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	if (!isNetworkAdmin() && snippet.network && !snippet.shared_network) {
		return (
			<span className="snippet-card-actions">
				{snippet.active
					? <span className="network-active">{__('Network Active', 'code-snippets')}</span>
					: <span className="network-only">{__('Network Only', 'code-snippets')}</span>}
			</span>
		)
	}

	if (snippet.shared_network && !window.CODE_SNIPPETS_MANAGE?.hasNetworkCap) {
		return null
	}

	return (
		<span className="snippet-card-actions">
			{!snippet.trashed && <EditCloneExportButtons snippet={snippet} />}

			{snippet.trashed && (
				<Button
					secondary
					onClick={() => {
						api.restore(snippet)
							.then(refreshSnippetsList)
							.catch(handleUnknownError)
					}}
				>
					{__('Restore', 'code-snippets')}
				</Button>)}

			{(!snippet.locked || snippet.trashed) && (
				<DeleteButton secondary className="snippet-card-delete" snippet={snippet} onSuccess={refreshSnippetsList} />)}
		</span>
	)
}

const CardActionsArea: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const [isExpanded, setIsExpanded] = useState(false)

	return (
		<>
			<footer>
				<CardPreviewButton snippet={snippet} />

				<Button
					primary
					className="snippet-card-more-toggle"
					aria-expanded={isExpanded}
					onClick={() => setIsExpanded(previous => !previous)}
				>
					{isExpanded ? __('Show less', 'code-snippets') : __('Show more', 'code-snippets')}
					<span className="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
				</Button>
			</footer>

			<div className={classnames('snippet-card-more', { 'is-expanded': isExpanded })}>
				<div className="snippet-card-more-inner">
					<div className="snippet-card-more-row">
						<span className="snippet-card-priority">
							<span aria-hidden="true">{__('Priority', 'code-snippets')}</span>
							<PriorityColumn snippet={snippet} />
						</span>

						<SnippetCardActions snippet={snippet} />
					</div>
				</div>
			</div>
		</>
	)
}

export interface ManageSnippetCardProps {
	snippet: Snippet
	isSelected: boolean
	onSelectedChange: (isSelected: boolean) => void
}

export const ManageSnippetCard: React.FC<ManageSnippetCardProps> = ({ snippet, isSelected, onSelectedChange }) => {
	const { activeByCondition } = useFilteredSnippets()

	return (
		<SnippetCard
			className={[
				'snippet',
				`${isSnippetActive(snippet, activeByCondition) ? 'active' : 'inactive'}-snippet`,
				`${getSnippetType(snippet)}-snippet`,
				`${snippet.scope}-snippet`
			].join(' ')}
			isSelected={isSelected}
			onSelectedChange={onSelectedChange}
			selectionLabel={sprintf(
				/* translators: %s: name of the snippet. */
				__('Select %s', 'code-snippets'),
				getSnippetDisplayName(snippet)
			)}
			cornerControls={<ActivateColumn snippet={snippet} />}
		>
			<div className="card-inner">
				<h3><SnippetName snippet={snippet} /></h3>

				<div className="snippet-card-meta">
					<TypeColumn snippet={snippet} />

					<span className="snippet-card-modified">
						<em>{__('Last Modified: ', 'code-snippets')}</em>
						<DateColumn snippet={snippet} />
					</span>

					{0 < snippet.tags.length && (
						<span className="snippet-card-tags">
							<span className="dashicons dashicons-tag" aria-hidden="true"></span>
							<TagsColumn snippet={snippet} />
						</span>)}

					<SnippetExtraIcons snippet={snippet} />
				</div>

				{snippet.desc
					? <div className="snippet-description-content"><RawHTML>{snippet.desc}</RawHTML></div>
					: null}
			</div>

			<CardActionsArea snippet={snippet} />
		</SnippetCard>
	)
}
