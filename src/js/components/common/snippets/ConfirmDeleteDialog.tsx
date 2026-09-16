import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { ConfirmDialog } from '../ConfirmDialog'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import type { Snippet } from '../../../types/Snippet'

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

export interface ConfirmDeleteDialogProps {
	snippet: Snippet
	isDialogOpen: boolean
	setIsDialogOpen: (isOpen: boolean) => void
	makeDeleteRequest: () => Promise<void>
}

export const ConfirmDeleteDialog: React.FC<ConfirmDeleteDialogProps> = ({ snippet, isDialogOpen, setIsDialogOpen, makeDeleteRequest }) =>
	<ConfirmDialog
		open={isDialogOpen}
		title={__('Are you sure?', 'code-snippets')}
		confirmLabel={snippet.trashed ? __('Delete', 'code-snippets') : __('Trash', 'code-snippets')}
		confirmButtonClassName="is-destructive"
		onCancel={() => setIsDialogOpen(false)}
		onConfirm={() => {
			setIsDialogOpen(false)
			void makeDeleteRequest()
		}}
	>
		{snippet.trashed
			? <PermanentDeleteConfirmMessage />
			: <TrashActiveConfirmMessage />}
	</ConfirmDialog>

export interface UseDeleteSnippetProps {
	snippet: Snippet
	setIsWorking?: (isWorking: boolean) => void
	onSuccess?: (() => Promise<void>) | VoidFunction
	onError?: (error: unknown) => void
}

export interface ConfirmDeleteDialog {
	requestDelete: () => Promise<void>
	deleteDialogProps: ConfirmDeleteDialogProps
}

export const useDeleteSnippet = ({
	snippet,
	setIsWorking,
	onSuccess,
	onError
}: UseDeleteSnippetProps): ConfirmDeleteDialog => {
	const snippetsAPI = useSnippetsAPI()
	const [isDialogOpen, setIsDialogOpen] = useState(false)

	const makeDeleteRequest = async () => {
		setIsWorking?.(true)

		try {
			await snippetsAPI.delete(snippet)
			await onSuccess?.()
		} catch (error) {
			onError?.(error)
		} finally {
			setIsWorking?.(false)
		}
	}

	const requestDelete = async () => {
		if (snippet.active || snippet.trashed) {
			setIsDialogOpen(true)
		} else {
			await makeDeleteRequest()
		}
	}

	return { requestDelete, deleteDialogProps: { snippet, isDialogOpen, setIsDialogOpen, makeDeleteRequest } }
}
