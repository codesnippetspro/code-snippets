import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { Button } from './Button'
import { ConfirmDialog } from './ConfirmDialog'
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

export interface DeleteButtonProps extends ButtonProps {
	snippet: Snippet
	setIsWorking?: (isWorking: boolean) => void
	onSuccess?: () => Promise<void> | void
	onError?: (error: unknown) => void
}

export const DeleteButton: React.FC<DeleteButtonProps> = ({
	snippet,
	onSuccess,
	onError,
	className = 'delete-button',
	setIsWorking,
	...buttonProps
}) => {
	const snippetsAPI = useSnippetsAPI()
	const [isDialogOpen, setIsDialogOpen] = useState(false)

	const handleDelete = () => {
		setIsWorking?.(true)

		snippetsAPI.delete(snippet)
			.then(() => onSuccess?.())
			.catch((error: unknown) => onError?.(error))
			.finally(() => setIsWorking?.(false))
	}

	const handleButtonClick = () => {
		if (snippet.active || snippet.trashed) {
			setIsDialogOpen(true)
		} else {
			handleDelete()
		}
	}

	return (
		<>
			<Button className={className} {...buttonProps} onClick={handleButtonClick}>
				{snippet.trashed ? __('Delete Permanently', 'code-snippets') : __('Trash', 'code-snippets')}
			</Button>

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
		</>
	)
}
