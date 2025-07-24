import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useRestAPI } from '../../hooks/useRestAPI'
import { Button } from './Button'
import { ConfirmDialog } from './ConfirmDialog'
import type { Snippet } from '../../types/Snippet'
import type { ButtonProps } from './Button'

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
	const { snippetsAPI } = useRestAPI()
	const [isDialogOpen, setIsDialogOpen] = useState(false)

	return (
		<>
			<Button
				{...buttonProps}
				className={className}
				onClick={() => {
					setIsDialogOpen(true)
				}}
			>
				{__('Delete', 'code-snippets')}
			</Button>

			<ConfirmDialog
				open={isDialogOpen}
				title={__('Permanently delete?', 'code-snippets')}
				confirmLabel={__('Delete', 'code-snippets')}
				confirmButtonClassName="is-destructive"
				onCancel={() => setIsDialogOpen(false)}
				onConfirm={() => {
					setIsDialogOpen(false)
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
				}}
			>
				<p style={{ marginBlockStart: 0 }}>
					{__('You are about to permanently delete this snippet.', 'code-snippets')}{' '}
					{__('Are you sure?', 'code-snippets')}
				</p>
				<p><strong>{__('This action cannot be undone.', 'code-snippets')}</strong></p>
			</ConfirmDialog>
		</>
	)
}
