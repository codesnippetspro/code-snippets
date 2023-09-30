import React from 'react'
import { Spinner } from '@wordpress/components'
import { useSnippetForm } from '../SnippetForm/context'
import { DeleteButton } from './DeleteButton'
import { ExportButtons } from './ExportButtons'
import { SubmitButton } from './SubmitButton'

export const ActionButtons: React.FC = () => {
	const { snippet, isWorking } = useSnippetForm()

	return (
		<p className="submit">
			<SubmitButton />

			{snippet.id ?
				<>
					<ExportButtons />
					<DeleteButton />
				</> : ''}

			{isWorking ? <Spinner /> : ''}
		</p>
	)
}
