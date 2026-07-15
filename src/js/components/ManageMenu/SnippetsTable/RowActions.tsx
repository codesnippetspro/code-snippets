import { Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import React, { useState } from 'react'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { downloadSnippetExportFile } from '../../../utils/files'
import { isNetworkAdmin } from '../../../utils/screen'
import { cloneSnippetObject, getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets/snippets'
import { Button } from '../../common/Button'
import { DeleteButton } from '../../common/DeleteButton'
import { SnippetPreviewModal } from '../../common/SnippetPreviewModal'
import type { ReactNode } from 'react'
import type { Snippet } from '../../../types/Snippet'

interface RowActionsProps {
	snippet: Snippet
}

const PreviewLink: React.FC<RowActionsProps> = ({ snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<>
			<Button link onClick={() => setIsPreviewOpen(true)}>
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

	const ButtonContent = () => {
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
	}

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
			<ButtonContent />
		</Button>
	)
}

const DeleteActionLink: React.FC<{ snippet: Snippet; onSuccess: () => Promise<void> }> = ({ snippet, onSuccess }) => {
	const [isDeleting, setIsDeleting] = useState(false)

	return (
		<>
			<DeleteButton
				link
				className={classnames('delete')}
				snippet={snippet}
				setIsWorking={setIsDeleting}
				onSuccess={onSuccess}
			/>
			{isDeleting ? <span className="snippet-row-action-feedback"><Spinner /> {__('Deleting…', 'code-snippets')}</span> : null}
		</>
	)
}

const ActionLinks = ({ snippet }: { snippet: Snippet }) => {
	const api = useSnippetsAPI()
	const { refreshSnippetsList } = useSnippetsList()

	const Preview = !snippet.trashed ? <PreviewLink snippet={snippet} /> : null

	const Edit = !snippet.trashed
		? <a href={getSnippetEditUrl(snippet)}>
			{snippet.locked ? __('View', 'code-snippets') : __('Edit', 'code-snippets')}
		</a>
		: null

	const Clone = !snippet.trashed
		? <SnippetActionButton
			label={__('Clone', 'code-snippets')}
			workingLabel={__('Cloning…', 'code-snippets')}
			action={() => api.create(cloneSnippetObject(snippet)).then(refreshSnippetsList)}
		/>
		: null

	const Export = !snippet.trashed
		? <SnippetActionButton
			label={__('Export', 'code-snippets')}
			workingLabel={__('Exporting…', 'code-snippets')}
			action={() =>
				api.export(snippet)
					.then(response => downloadSnippetExportFile(response, snippet))}
		/>
		: null

	const Restore = snippet.trashed
		? <SnippetActionButton
			label={__('Restore', 'code-snippets')}
			workingLabel={__('Restoring…', 'code-snippets')}
			action={() => api.restore(snippet).then(refreshSnippetsList)}
		/>
		: null

	const Delete = !snippet.locked || snippet.trashed
		? <DeleteActionLink snippet={snippet} onSuccess={refreshSnippetsList} />
		: null

	return (
		<>
			{[Preview, Edit, Clone, Restore, Export, Delete]
				.filter(Action => Action)
				.reduce<ReactNode>(
					(Actions, Action) =>
						null === Actions ? <>{Action}</> : <>{Actions} | {Action}</>,
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
		return undefined
	}

	return (
		<div className={classnames('row-actions', { visible: !snippet.trashed })}>
			<ActionLinks snippet={snippet} />
		</div>
	)
}
