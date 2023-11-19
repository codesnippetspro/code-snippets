import { addQueryArgs } from '@wordpress/url'
import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { ConfirmDialog } from '../../common/ConfirmDialog'
import { useSnippetsAPI } from '../../utils/api/snippets'
import { useSnippetForm } from '../SnippetForm/context'

export const DeleteButton: React.FC = () => {
	const api = useSnippetsAPI()
	const { snippet, setIsWorking, isWorking, handleRequestError } = useSnippetForm()
	const [isDialogOpen, setIsDialogOpen] = useState(false)

	return (
		<>
			<Button
				name="delete_snippet"
				onClick={() => setIsDialogOpen(true)}
				disabled={isWorking}
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
					setIsWorking(true)

					api.delete(snippet)
						.then(() => {
							setIsWorking(false)
							window.location.replace(addQueryArgs(window?.CODE_SNIPPETS?.urls.manage, { result: 'deleted' }))
						})
						.catch(error => handleRequestError(error, __('Could not delete snippet.', 'code-snippets')))
				}}
			>
				<p>
					{__('You are about to permanently delete this snippet.', 'code-snippets')}{' '}
					{__('Are you sure?', 'code-snippets')}
				</p>
				<p><strong>{__('This action cannot be undone.', 'code-snippets')}</strong></p>
			</ConfirmDialog>
		</>
	)
}
