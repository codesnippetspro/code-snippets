import { humanTimeDiff } from '@wordpress/date'
import { RawHTML } from '@wordpress/element'
import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { downloadSnippetExportFile } from '../../../utils/files'
import { canModifySnippet, cloneSnippetObject, getSnippetDisplayName, getSnippetEditUrl, getSnippetType, isNetworkOnlySnippet, isSnippetActive } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { useDeleteSnippet } from '../../common/DeleteButton'
import { KebabMenu, KebabMenuDivider, KebabMenuItem, KebabMenuRow } from '../../common/KebabMenu'
import { SnippetCard } from '../../common/SnippetCard'
import { SnippetPreviewModal } from '../../common/SnippetPreviewModal'
import { useFilteredSnippets } from './WithFilteredSnippetsContext'
import { ActivateColumn, PriorityColumn, SnippetExtraIcons, SnippetName, TagsColumn, TypeColumn } from './TableColumns'
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
				snippet={snippet}
			/>
		</>
	)
}

const CloneExportMenuItems: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	return (
		<>
			<KebabMenuItem
				onSelect={() => {
					api.create(cloneSnippetObject(snippet))
						.then(refreshSnippetsList)
						.catch(handleUnknownError)
				}}
			>
				{__('Clone', 'code-snippets')}
			</KebabMenuItem>

			<KebabMenuItem
				onSelect={() => {
					api.export(snippet)
						.then(response => downloadSnippetExportFile(response, snippet))
						.catch(handleUnknownError)
				}}
			>
				{__('Export', 'code-snippets')}
			</KebabMenuItem>

			<KebabMenuDivider />
		</>
	)
}

interface RestoreDeleteMenuItemsProps extends SnippetCardActionsProps {
	requestDelete: () => void
}

const RestoreDeleteMenuItems: React.FC<RestoreDeleteMenuItemsProps> = ({
	snippet,
	requestDelete
}) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	return (
		<>
			<KebabMenuDivider />

			{snippet.trashed
				? <KebabMenuItem
					onSelect={() => {
						api.restore(snippet)
							.then(refreshSnippetsList)
							.catch(handleUnknownError)
					}}
				>
					{__('Restore', 'code-snippets')}
				</KebabMenuItem>
				: null}

			<KebabMenuItem destructive onSelect={requestDelete}>
				{snippet.trashed ? __('Delete Permanently', 'code-snippets') : __('Trash', 'code-snippets')}
			</KebabMenuItem>
		</>
	)
}

const CardActionsMenu: React.FC<SnippetCardActionsProps> = ({ snippet }) => {
	const { refreshSnippetsList } = useSnippetsList()
	const canModify = canModifySnippet(snippet)
	const { requestDelete, confirmDialog } = useDeleteSnippet({
		snippet,
		onSuccess: refreshSnippetsList,
		onError: handleUnknownError
	})

	return (
		<>
			<KebabMenu
				label={sprintf(
					/* translators: %s: name of the snippet. */
					__('Actions for %s', 'code-snippets'),
					getSnippetDisplayName(snippet)
				)}
			>
				{canModify && !snippet.trashed ? <CloneExportMenuItems snippet={snippet} /> : null}

				<KebabMenuRow className="kebab-menu-priority">
					<label htmlFor={`snippet-${snippet.id}-priority`}>{__('Priority', 'code-snippets')}</label>
					<PriorityColumn snippet={snippet} />
				</KebabMenuRow>

				{canModify && (snippet.trashed || !snippet.locked)
					? <RestoreDeleteMenuItems snippet={snippet} requestDelete={requestDelete} />
					: null}
			</KebabMenu>

			{confirmDialog}
		</>
	)
}

const CardFooterActions: React.FC<SnippetCardActionsProps> = ({ snippet }) =>
	<>
		{isNetworkOnlySnippet(snippet)
			? snippet.active
				? <span className="network-active">{__('Network Active', 'code-snippets')}</span>
				: <span className="network-only">{__('Network Only', 'code-snippets')}</span>
			: null}

		{!snippet.trashed ? <CardPreviewButton snippet={snippet} /> : null}

		{!snippet.trashed && canModifySnippet(snippet)
			? <a className="button button-primary" href={getSnippetEditUrl(snippet)}>
				{snippet.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
			</a>
			: null}

		<CardActionsMenu snippet={snippet} />
	</>

const CardModifiedDate: React.FC<SnippetCardActionsProps> = ({ snippet }) =>
	snippet.modified
		? <time className="snippet-card-modified" dateTime={snippet.modified} title={snippet.modified}>
			{sprintf(
				/* translators: %s: human-readable time difference, including "ago" suffix. */
				__('Modified %s', 'code-snippets'),
				humanTimeDiff(snippet.modified, undefined)
			)}
		</time>
		: null

export interface ManageSnippetCardProps {
	snippet: Snippet
	isSelected: boolean
	onSelectedChange: (isSelected: boolean) => void
}

export const ManageSnippetCard: React.FC<ManageSnippetCardProps> = ({
	snippet,
	isSelected,
	onSelectedChange
}) => {
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
			footer={<CardFooterActions snippet={snippet} />}
		>
			<div className="card-inner">
				<div className="snippet-card-header">
					<ActivateColumn snippet={snippet} />
					<TypeColumn snippet={snippet} />
					<h3><SnippetName snippet={snippet} /></h3>
					<SnippetExtraIcons snippet={snippet} />
				</div>

				{0 < snippet.tags.length || snippet.modified
					? <div className="snippet-card-meta">
						{0 < snippet.tags.length
							? <span className="snippet-card-tags">
								<span className="snippet-card-tags-label">{__('Tags:', 'code-snippets')}</span>
								{' '}
								<TagsColumn snippet={snippet} />
							</span>
							: null}

						<CardModifiedDate snippet={snippet} />
					</div>
					: null}

				{snippet.desc
					? <div className="snippet-description-content"><RawHTML>{snippet.desc}</RawHTML></div>
					: null}
			</div>
		</SnippetCard>
	)
}
