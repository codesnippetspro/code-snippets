import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetsAPI } from '../../hooks/useSnippetsAPI'
import { Button } from './Button'
import { ConfirmDialog } from './ConfirmDialog'
import type { Snippet } from '../../types/Snippet'
import type { ButtonProps } from './Button'
import { createInterpolateElement } from '@wordpress/element'

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
			.then(() => {
				setIsWorking?.(false)
				return onSuccess?.()
			})
			.catch((error: unknown) => {
				setIsWorking?.(false)
				return onError?.(error)
			})
	}

	return (
		<>
			<Button
				{...buttonProps}
				className={className}
				onClick={() => {
					if (snippet.active || snippet.trashed) {
						setIsDialogOpen(true)
					} else {
						handleDelete()
					}
				}}
			>
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
				<p style={{ marginBlockStart: 0 }}>
					{createInterpolateElement(
						snippet.trashed
							? __('The snippet will be <strong>permanently deleted</strong>.', 'code-snippets')
							: __('This snippet is currently <strong>active</strong>.', 'code-snippets'),
						{ strong: <strong /> }
					)}
				</p>

				<p>{snippet.trashed
					? __('This action cannot be undone.', 'code-snippets')
					: __('Moving it to the trash will also deactivate it.', 'code-snippets')}</p>
			</ConfirmDialog>
		</>
	)
}
