import { Spinner, Tooltip } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import React, { useState } from 'react'
import { ButtonProps } from '../../common/Button'
import { Snippet } from '../../../types/Snippet'
import { ExplainedSnippet, ExplainSnippetFields, useGenerativeAPI } from '../../../hooks/useGenerativeAPI'
import { trimTrailingChar } from '../../../utils/text'
import { CloudAIButton } from './CloudAIButton'

export interface ExplainSnippetButtonProps extends Omit<ButtonProps, 'onClick'> {
	field: ExplainSnippetFields
	snippet: Snippet
	onRequest?: VoidFunction
	onResponse?: (generated: ExplainedSnippet) => void
}

export const ExplainSnippetButton: React.FC<ExplainSnippetButtonProps> = ({
	field,
	snippet,
	disabled,
	onRequest,
	onResponse,
	...props
}) => {
	const [isWorking, setIsWorking] = useState(false)
	const [errorMessage, setErrorMessage] = useState<string>()
	const { explainSnippet } = useGenerativeAPI()

	return (
		<div className="generate-button">
			{isWorking ? <Spinner /> : null}

			{errorMessage ?
				<Tooltip text={`${trimTrailingChar(errorMessage, '.')}. ${__('Please try again.', 'code-snippets')}`}>
					<div>
						<span className="dashicons dashicons-warning"></span>
					</div>
				</Tooltip> : null}

			<CloudAIButton
				{...props}
				snippet={snippet}
				disabled={disabled ?? isWorking}
				onClick={() => {
					setIsWorking(true)
					setErrorMessage(undefined)
					onRequest?.()

					explainSnippet(snippet.code, field)
						.then(response => {
							setIsWorking(false)
							onResponse?.(response)
						})
						.catch((error: unknown) => {
							setIsWorking(false)
							setErrorMessage(isAxiosError(error) ?
								error.message :
								__('An unknown error occurred.', 'code-snippets'))
						})
				}}
			/>
		</div>
	)
}
