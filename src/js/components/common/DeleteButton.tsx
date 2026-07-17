import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { Button } from './Button'
import { ConfirmDialog } from './ConfirmDialog'
import type { ReactNode } from 'react'
import type { Snippet } from '../../types/Snippet'
import type { ButtonProps } from './Button'

const TrashActiveConfirmMessage = () =>
	<>
		<p style={{ marginBlockStart: 0 }}>
			{createInterpolateElement(
				__('This snippet is currently <strong>active</strong>.', 'code-snippets'),
				{ strong: <strong /> }
			)}
		</p>

		<p>{__('Moving it to the trash will also deactivate it.', 'code-snippets')}</p>
	</>

const PermanentDeleteConfirmMessage = () =>
	<>
		<p style={{ marginBlockStart: 0 }}>
			{createInterpolateElement(
				__('The snippet will be <strong>permanently deleted</strong>.', 'code-snippets'),
				{ strong: <strong /> }
			)}
		</p>

		<p>{__('This action cannot be undone.', 'code-snippets')}</p>
	</>

export interface UseDeleteSnippetOptions {
	snippet: Snippet
	setIsWorking?: (isWorking: boolean) => void
	onSuccess?: () => Promise<void> | void
	onError?: (error: unknown) => void
}

export interface UseDeleteSnippetResult {
	requestDelete: () => void
	confirmDialog: ReactNode
}

/**
 * Deletion flow shared by every control that removes a snippet: active and
 * trashed snippets prompt for confirmation before the API call, while
 * inactive snippets are trashed immediately. Callers must render the returned
 * dialog somewhere that stays mounted while the confirmation is open.
 */
export const useDeleteSnippet = ({
	snippet,
	setIsWorking,
	onSuccess,
	onError
}: UseDeleteSnippetOptions): UseDeleteSnippetResult => {
	const snippetsAPI = useSnippetsAPI()
	const [isDialogOpen, setIsDialogOpen] = useState(false)

	const handleDelete = () => {
		setIsWorking?.(true)

		snippetsAPI.delete(snippet)
			.then(() => onSuccess?.())
			.catch((error: unknown) => onError?.(error))
			.finally(() => setIsWorking?.(false))
	}

	const requestDelete = () => {
		if (snippet.active || snippet.trashed) {
			setIsDialogOpen(true)
		} else {
			handleDelete()
		}
	}

	const confirmDialog =
		<ConfirmDialog
			open={isDialogOpen}
			title={__('Are you sure?', 'code-snippets')}
			confirmLabel={snippet.trashed ? __('Delete', 'code-snippets') : __('Trash', 'code-snippets')}
			confirmButtonClassName="is-destructive"
			onCancel={() => setIsDialogOpen(false)}
			onConfirm={() => {
				setIsDialogOpen(false)
				handleDelete()
			}}
		>
			{snippet.trashed ? <PermanentDeleteConfirmMessage /> : <TrashActiveConfirmMessage />}
		</ConfirmDialog>

	return { requestDelete, confirmDialog }
}

export interface DeleteButtonProps
	extends Omit<ButtonProps, 'onError'>, Omit<UseDeleteSnippetOptions, 'snippet'> {
	snippet: Snippet
}

export const DeleteButton: React.FC<DeleteButtonProps> = ({
	snippet,
	onSuccess,
	onError,
	className = 'delete-button',
	setIsWorking,
	...buttonProps
}) => {
	const { requestDelete, confirmDialog } = useDeleteSnippet({
		snippet,
		setIsWorking,
		onSuccess,
		onError
	})

	return (
		<>
			<Button className={className} {...buttonProps} onClick={requestDelete}>
				{snippet.trashed ? __('Delete Permanently', 'code-snippets') : __('Trash', 'code-snippets')}
			</Button>

			{confirmDialog}
		</>
	)
}
