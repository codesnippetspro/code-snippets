import React from 'react'
import { Spinner } from '@wordpress/components'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { DeleteButton } from './DeleteButton'
import { ExportButtons } from './ExportButtons'
import { SubmitButton } from './SubmitButtons'

export const ActionButtons: React.FC = () => {
	const { snippet, isWorking } = useSnippetForm()

	return (
		<p className="submit">
			<SubmitButton />

			{snippet.id
				? <>
					<ExportButtons />
					<DeleteButton />
				</>
				: ''}

			{isWorking ? <Spinner /> : ''}
		</p>
	)
}
