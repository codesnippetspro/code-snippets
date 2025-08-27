import { Spinner } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import React, { useState } from 'react'
import { useGenerativeAPI } from '../../../hooks/useGenerativeAPI'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { isCondition } from '../../../utils/snippets/snippets'
import { trimTrailingChar } from '../../../utils/text'
import { Tooltip } from '../../common/Tooltip'
import { CloudAIButton } from '../../EditorSidebar/actions/CloudAIButton'
import type { ButtonProps } from '../../common/Button'
import type { ExplainSnippetFields, ExplainedSnippet } from '../../../hooks/useGenerativeAPI'

export interface ExplainSnippetButtonProps extends Omit<ButtonProps, 'onClick'> {
	field: ExplainSnippetFields
	onRequest?: VoidFunction
	onResponse?: (generated: ExplainedSnippet) => void
}

export const ExplainSnippetButton: React.FC<ExplainSnippetButtonProps> = ({
	field,
	onRequest,
	onResponse,
	...buttonProps
}) => {
	const { snippet, isReadOnly } = useSnippetForm()
	const [isWorking, setIsWorking] = useState(false)
	const [errorMessage, setErrorMessage] = useState<string>()
	const { explainSnippet } = useGenerativeAPI()

	return '' !== snippet.code.trim() || isCondition(snippet)
		? <div className="generate-button">
			{isWorking ? <Spinner /> : null}

			{errorMessage
				? <Tooltip block end icon={<span className="dashicons dashicons-warning"></span>}>
					{`${trimTrailingChar(errorMessage, '.')}. ${__('Please try again.', 'code-snippets')}`}
				</Tooltip>
				: null}

			<CloudAIButton
				snippet={snippet}
				disabled={isReadOnly || isWorking}
				{...buttonProps}
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
							setErrorMessage(isAxiosError(error)
								? error.message
								: __('An unknown error occurred.', 'code-snippets'))
						})
				}}
			/>
		</div>
		: null
}
