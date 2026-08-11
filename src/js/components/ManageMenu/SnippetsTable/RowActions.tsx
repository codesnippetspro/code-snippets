import { Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { downloadSnippetExportFile } from '../../../utils/files'
import { isNetworkAdmin } from '../../../utils/screen'
import { cloneSnippetObject, getSnippetEditUrl } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { SnippetPreviewModal } from '../../common/snippets/SnippetPreviewModal'
import { ConfirmDeleteDialog, useDeleteSnippet } from '../../common/snippets/ConfirmDeleteDialog'
import type { ReactNode } from 'react'
import type { Snippet } from '../../../types/Snippet'

interface RowActionsProps {
	snippet: Snippet
}

const PreviewButton: React.FC<RowActionsProps> = ({ snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<>
			<Button link onClick={() => setIsPreviewOpen(true)}>
				{__('Preview', 'code-snippets')}
			</Button>

			{isPreviewOpen && <SnippetPreviewModal snippet={snippet} setIsOpen={setIsPreviewOpen} />}
		</>
	)
}

interface SnippetActionButtonProps {
	action: () => Promise<unknown>
	label: ReactNode
	workingLabel?: string
	failedLabel?: string
	className?: string
}

enum SnippetActionStatus { Ready, Working, Errored }

const SnippetActionButton: React.FC<SnippetActionButtonProps> = ({ action, label, workingLabel, failedLabel, className }) => {
	const [status, setStatus] = useState(SnippetActionStatus.Ready)

	return (
		<Button
			link
			className={classnames(className, { 'is-busy': status === SnippetActionStatus.Working })}
			disabled={status === SnippetActionStatus.Working}
			onClick={() => {
				switch (status) {
					case SnippetActionStatus.Errored:
						setStatus(SnippetActionStatus.Ready)
						break

					case SnippetActionStatus.Working:
						break

					case SnippetActionStatus.Ready: {
						setStatus(SnippetActionStatus.Working)

						action()
							.then(() => setStatus(SnippetActionStatus.Ready))
							.catch(() => {
								setStatus(SnippetActionStatus.Errored)
							})

						break
					}
				}
			}}
		>
			{(() => {
				switch (status) {
					case SnippetActionStatus.Working:
						return (
							<span className="snippet-row-action-feedback">
								<Spinner /> {workingLabel ?? __('Loading…', 'code-snippets')}
							</span>
						)

					case SnippetActionStatus.Errored:
						return (
							<span className="snippet-row-action-error">
								<span className="dashicons dashicons-warning"></span>
								{failedLabel ?? __('Failed', 'code-snippets')}
							</span>
						)

					default:
						return label
				}
			})()}
		</Button>
	)
}

const DeleteActionLink: React.FC<RowActionsProps> = ({ snippet }) => {
	const { refreshSnippetsList } = useSnippetsList()
	const { requestDelete, deleteDialogProps } = useDeleteSnippet({ snippet, onSuccess: refreshSnippetsList })

	return (
		<>
			<SnippetActionButton
				action={requestDelete}
				className="delete"
				label={snippet.trashed ? __('Delete Permanently', 'code-snippets') : __('Trash', 'code-snippets')}
				workingLabel={snippet.trashed ? __('Deleting…', 'code-snippets') : __('Trashing…', 'code-snippets')}
				failedLabel={__('Failed to delete', 'code-snippets')}
			/>

			<ConfirmDeleteDialog {...deleteDialogProps} />
		</>
	)
}

const ActionLinks: React.FC<RowActionsProps> = ({ snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const previewButton = !snippet.trashed ? <PreviewButton snippet={snippet} /> : null

	const editLink = !snippet.trashed
		? <a href={getSnippetEditUrl(snippet)}>
			{snippet.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
		</a>
		: null

	const cloneButton = !snippet.trashed
		? <SnippetActionButton
			label={__('Clone', 'code-snippets')}
			workingLabel={__('Cloning…', 'code-snippets')}
			action={() => api.create(cloneSnippetObject(snippet)).then(refreshSnippetsList)}
		/>
		: null

	const exportButton = !snippet.trashed
		? <SnippetActionButton
			label={__('Export', 'code-snippets')}
			workingLabel={__('Exporting…', 'code-snippets')}
			action={() =>
				api.export(snippet)
					.then(response => downloadSnippetExportFile(response, snippet))}
		/>
		: null

	const restoreButton = snippet.trashed
		? <SnippetActionButton
			label={__('Restore', 'code-snippets')}
			workingLabel={__('Restoring…', 'code-snippets')}
			action={() => api.restore(snippet).then(refreshSnippetsList)}
		/>
		: null

	const deleteButton = !snippet.locked || snippet.trashed
		? <DeleteActionLink snippet={snippet} />
		: null

	return (
		<>
			{[previewButton, editLink, cloneButton, restoreButton, exportButton, deleteButton]
				.filter(action => action)
				.reduce<ReactNode>(
					(actions, action) =>
						null === actions ? <>{action}</> : <>{actions} | {action}</>,
					null)}
		</>
	)
}

export const RowActions: React.FC<RowActionsProps> = ({ snippet }) => {
	if (!isNetworkAdmin() && snippet.network && !snippet.shared_network) {
		return (
			<div className="row-actions visible">
				{snippet.active
					? <span className="network-active">{__('Network Active', 'code-snippets')}</span>
					: <span className="network-only">{__('Network Only', 'code-snippets')}</span>}
			</div>
		)
	}

	if (snippet.shared_network && !window.CODE_SNIPPETS_MANAGE?.hasNetworkCap) {
		return null
	}

	return (
		<div className={classnames('row-actions', { visible: !snippet.trashed })}>
			<ActionLinks snippet={snippet} />
		</div>
	)
}
